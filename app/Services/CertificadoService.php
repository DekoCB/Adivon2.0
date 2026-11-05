<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class CertificadoService
{
    /**
     * Intenta primero con openssl_pkcs12_read() (extensión nativa de PHP,
     * sin procesos externos — funciona en producción aunque proc_open/exec
     * estén deshabilitados, como ocurre en el hosting de Hostinger). Esto
     * cubre certificados con cifrado moderno (AES), que es lo que exportan
     * hoy la mayoría de herramientas.
     *
     * Si el PFX usa cifrado antiguo (RC2/3DES — típico en certificados SUNAT
     * viejos), OpenSSL 3.x necesita su "provider" legacy, que solo se puede
     * activar vía la variable de entorno OPENSSL_CONF leída ANTES de que el
     * proceso de PHP inicie — imposible de cambiar en caliente dentro de un
     * request (confirmado: ni putenv() ni .user.ini con auto_prepend_file
     * sirven, porque OpenSSL ya leyó su configuración al arrancar el
     * proceso/worker, antes de que corra cualquier código del script). En
     * ese caso caemos a un proceso externo de openssl -legacy, que sí puede
     * recibir ese entorno desde que se crea — pero solo funciona donde
     * proc_open esté habilitado (entornos de desarrollo, no este hosting).
     */
    public function convertirPfxAPem(UploadedFile $pfxFile, string $password, Empresa $empresa): string
    {
        $pfxContent = file_get_contents($pfxFile->getRealPath());

        $pem = $this->leerConExtensionNativa($pfxContent, $password, $erroresNativo);

        if ($pem === null && $this->esErrorDeCifradoNoSoportado($erroresNativo) && function_exists('proc_open')) {
            $pem = $this->leerConProcesoExterno($pfxFile, $password);
        }

        if ($pem === null) {
            throw new \Exception($this->traducirError($erroresNativo));
        }

        $dir = "certificados/{$empresa->ruc}";
        $path = "{$dir}/certificado.pem";

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($path, $pem);

        return $path;
    }

    private function leerConExtensionNativa(string $pfxContent, string $password, ?string &$erroresOut): ?string
    {
        $certs = [];
        $ok = @openssl_pkcs12_read($pfxContent, $certs, $password);
        $erroresOut = $this->drenarErroresOpenssl();

        if (!$ok || empty($certs['cert']) || empty($certs['pkey'])) {
            return null;
        }

        return $certs['pkey'] . $certs['cert'];
    }

    private function leerConProcesoExterno(UploadedFile $pfxFile, string $password): ?string
    {
        $opensslBin = $this->resolveOpensslBinary();
        $env = ['CERT_PASS' => $password] + $this->resolveOpensslEnv();

        $tmpPfx = tempnam(sys_get_temp_dir(), 'pfx_');
        $tmpCert = tempnam(sys_get_temp_dir(), 'cert_');
        $tmpKey = tempnam(sys_get_temp_dir(), 'key_');

        try {
            copy($pfxFile->getRealPath(), $tmpPfx);

            // -legacy fuerza el soporte de RC2/3DES (cifrado típico de certificados
            // SUNAT antiguos) que OpenSSL 3.x desactiva por defecto.
            $certResult = Process::env($env)->run([
                $opensslBin, 'pkcs12', '-legacy', '-in', $tmpPfx,
                '-passin', 'env:CERT_PASS', '-nokeys', '-out', $tmpCert,
            ]);

            if (!$certResult->successful()) {
                throw new \Exception($this->traducirError($certResult->errorOutput()));
            }

            $keyResult = Process::env($env)->run([
                $opensslBin, 'pkcs12', '-legacy', '-in', $tmpPfx,
                '-passin', 'env:CERT_PASS', '-nocerts', '-nodes', '-out', $tmpKey,
            ]);

            if (!$keyResult->successful()) {
                throw new \Exception($this->traducirError($keyResult->errorOutput()));
            }

            $certContent = file_get_contents($tmpCert);
            $keyContent = file_get_contents($tmpKey);

            if (trim((string) $certContent) === '' || trim((string) $keyContent) === '') {
                return null;
            }

            return $keyContent . $certContent;
        } finally {
            @unlink($tmpPfx);
            @unlink($tmpCert);
            @unlink($tmpKey);
        }
    }

    private function drenarErroresOpenssl(): string
    {
        $mensajes = [];
        while ($msg = openssl_error_string()) {
            $mensajes[] = $msg;
        }

        return implode(' | ', $mensajes);
    }

    private function esErrorDeCifradoNoSoportado(string $errores): bool
    {
        return str_contains(strtolower($errores), 'unsupported');
    }

    private function resolveOpensslBinary(): string
    {
        $windowsBin = env('OPENSSL_BIN_PATH', 'C:\\xampp\\apache\\bin\\openssl.exe');

        if (PHP_OS_FAMILY === 'Windows' && file_exists($windowsBin)) {
            return $windowsBin;
        }

        return 'openssl';
    }

    /**
     * El openssl.exe de XAMPP (Apache) fue compilado con una ruta de módulos
     * ("ossl-modules") que no existe en esta instalación, por lo que no
     * encuentra el provider "legacy" (RC2/3DES) por sí solo. Apuntamos
     * explícitamente al legacy.dll que sí viene incluido junto a PHP.
     */
    private function resolveOpensslEnv(): array
    {
        $modulesDir = env('OPENSSL_MODULES_DIR', 'C:\\xampp\\php\\extras\\ssl');

        if (PHP_OS_FAMILY === 'Windows' && is_dir($modulesDir)) {
            return ['OPENSSL_MODULES' => $modulesDir];
        }

        return [];
    }

    private function traducirError(string $errorOutput): string
    {
        $lower = strtolower($errorOutput);

        if (str_contains($lower, 'mac verify') || str_contains($lower, 'wrong password') || str_contains($lower, 'bad decrypt')) {
            return 'No se pudo leer el certificado PFX. Verifique la contraseña.';
        }

        if (str_contains($lower, 'unsupported')) {
            return 'Este certificado usa un cifrado antiguo (RC2 o 3DES) que este servidor no puede leer '
                . 'directamente. Pida a quien le dio el certificado un archivo .pfx nuevo con cifrado AES '
                . '(la mayoría de herramientas modernas, como KeyStore Explorer, lo hacen por defecto), '
                . 'o conviértalo usted mismo en una PC con "openssl pkcs12 -legacy" para descifrarlo y '
                . 'luego "openssl pkcs12 -export -certpbe AES-256-CBC -keypbe AES-256-CBC" para '
                . 're-encriptarlo, y vuelva a subir ese archivo.';
        }

        return 'No se pudo procesar el certificado PFX: ' . trim($errorOutput);
    }
}
