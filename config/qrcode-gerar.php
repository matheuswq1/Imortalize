<?php
/**
 * Geração de QR Code (PNG) — Imortalize
 * Depende de: chillerlan/php-qrcode e extensão GD (ext-gd) no PHP.
 * Suporta: layout padrão, módulos circulares, espaço para logo ao centro.
 */

if (!function_exists('gerarQrCodePng')) {

  /** Último erro da geração (para diagnóstico). */
  $_qr_code_last_error = null;

  function getLastQrCodeError() {
    global $_qr_code_last_error;
    return $_qr_code_last_error;
  }

  /**
   * Composita o logo ao centro da imagem do QR (PNG).
   * O logo é limitado ao espaço reservado (retângulo) para não invadir os módulos.
   * @param string $qrPath Caminho absoluto do PNG do QR
   * @param string $logoPath Caminho absoluto do logo (PNG/JPG)
   * @param float $ratio Proporção máxima do logo (usado só se maxW/maxH não forem passados)
   * @param int|null $maxLogoPixels Tamanho máximo em pixels (quadrado; usado só sem maxW/maxH)
   * @param int|null $maxW Largura máxima do espaço do logo em pixels (retângulo)
   * @param int|null $maxH Altura máxima do espaço do logo em pixels (retângulo)
   * @return bool
   */
  function qrcodeMergeLogo($qrPath, $logoPath, $ratio = 0.28, $maxLogoPixels = null, $maxW = null, $maxH = null) {
    if (!is_file($qrPath) || !is_file($logoPath)) {
      return false;
    }
    $img = @imagecreatefrompng($qrPath);
    if (!$img) {
      return false;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $size = min($w, $h);

    if ($maxW !== null && $maxH !== null && $maxW > 0 && $maxH > 0) {
      $limitW = $maxW;
      $limitH = $maxH;
    } else {
      $logoSize = (int) round($size * $ratio);
      if ($maxLogoPixels !== null && $maxLogoPixels > 0) {
        $logoSize = min($logoSize, $maxLogoPixels);
      }
      $limitW = $limitH = $logoSize;
    }

    if ($limitW < 10 || $limitH < 10) {
      imagedestroy($img);
      return false;
    }

    $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
    $logo = null;
    if ($ext === 'png') {
      $logo = @imagecreatefrompng($logoPath);
    } elseif (in_array($ext, ['jpg', 'jpeg'], true)) {
      $logo = @imagecreatefromjpeg($logoPath);
    }
    if (!$logo) {
      imagedestroy($img);
      return false;
    }
    imagesavealpha($img, true);
    imagealphablending($img, true);
    $logoW = imagesx($logo);
    $logoH = imagesy($logo);
    if ($logoW <= 0 || $logoH <= 0) {
      imagedestroy($img);
      imagedestroy($logo);
      return false;
    }
    $scale = min($limitW / $logoW, $limitH / $logoH, 1.0);
    $drawW = (int) round($logoW * $scale);
    $drawH = (int) round($logoH * $scale);
    if ($drawW < 1) $drawW = 1;
    if ($drawH < 1) $drawH = 1;
    $dstX = (int) round(($w - $drawW) / 2);
    $dstY = (int) round(($h - $drawH) / 2);
    imagecopyresampled($img, $logo, $dstX, $dstY, 0, 0, $drawW, $drawH, $logoW, $logoH);
    imagedestroy($logo);
    $ok = imagepng($img, $qrPath);
    imagedestroy($img);
    return $ok;
  }

  /**
   * Gera imagem PNG do QR Code e salva em arquivo.
   * @param string $url Conteúdo do QR (ex: URL de redirect)
   * @param string $savePath Caminho absoluto para salvar o PNG
   * @param array $opcoes ['layout_style' => 'padrao'|'circular'|'com_logo', 'logo_path' => caminho absoluto do logo]
   * @return bool
   */
  function gerarQrCodePng($url, $savePath, array $opcoes = []) {
    global $_qr_code_last_error;
    $_qr_code_last_error = null;

    if (!extension_loaded('gd')) {
      $_qr_code_last_error = 'Extensão GD não está ativa. Ative em php.ini: extension=gd';
      return false;
    }

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
      $_qr_code_last_error = 'vendor/autoload.php não encontrado. Execute: composer require chillerlan/php-qrcode';
      return false;
    }
    require_once $autoload;

    $savePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $savePath);
    $dir = dirname($savePath);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
      $_qr_code_last_error = 'Não foi possível criar a pasta: ' . $dir;
      return false;
    }
    if (!is_writable($dir)) {
      $_qr_code_last_error = 'Pasta sem permissão de escrita: ' . $dir;
      return false;
    }

    $layout_style = isset($opcoes['layout_style']) && in_array($opcoes['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $opcoes['layout_style'] : 'padrao';
    $logo_path = isset($opcoes['logo_path']) && is_string($opcoes['logo_path']) ? $opcoes['logo_path'] : null;

    $useLogoSpace = ($layout_style === 'com_logo');
    $useCircular = ($layout_style === 'circular');

    $scale = 10;
    $logoSpaceModulesW = 12;
    $logoSpaceModulesH = 12;

    if ($useLogoSpace && $logo_path !== null && is_file($logo_path)) {
      $logoSize = @getimagesize($logo_path);
      if ($logoSize && isset($logoSize[0], $logoSize[1]) && $logoSize[0] > 0 && $logoSize[1] > 0) {
        $lw = $logoSize[0];
        $lh = $logoSize[1];
        $baseModules = 12;
        $minModules = 5;
        if ($lw >= $lh) {
          $logoSpaceModulesW = $baseModules;
          $logoSpaceModulesH = max($minModules, (int) round($baseModules * $lh / $lw));
        } else {
          $logoSpaceModulesH = $baseModules;
          $logoSpaceModulesW = max($minModules, (int) round($baseModules * $lw / $lh));
        }
      }
    }

    $optionsArray = [
      'outputType'   => 'png',
      'scale'        => $scale,
      'outputBase64' => false,
    ];

    if ($useLogoSpace) {
      $optionsArray['eccLevel'] = \chillerlan\QRCode\Common\EccLevel::H;
      $optionsArray['addLogoSpace'] = true;
      $optionsArray['logoSpaceWidth'] = $logoSpaceModulesW;
      $optionsArray['logoSpaceHeight'] = $logoSpaceModulesH;
    }

    if ($useCircular) {
      $optionsArray['drawCircularModules'] = true;
      $optionsArray['gdImageUseUpscale'] = true;
    }

    try {
      $options = new \chillerlan\QRCode\QROptions($optionsArray);
      $qr = new \chillerlan\QRCode\QRCode($options);
      $qr->render($url, $savePath);

      if (is_file($savePath) && $useLogoSpace && $logo_path !== null && is_file($logo_path)) {
        $spaceW = (int) floor($logoSpaceModulesW * $scale * 0.98);
        $spaceH = (int) floor($logoSpaceModulesH * $scale * 0.98);
        qrcodeMergeLogo($savePath, $logo_path, 0.28, null, $spaceW, $spaceH);
      }
      return is_file($savePath);
    } catch (Throwable $e) {
      $_qr_code_last_error = $e->getMessage();
      return false;
    }
  }
}
