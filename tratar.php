<?php
set_time_limit(0);
require 'HeicToJpg.php';

$marcaDagua = true;
$miniaturas = true;
$assinatura = "";

$root = $_SERVER['DOCUMENT_ROOT']  . DIRECTORY_SEPARATOR . 'trataimagens'  . DIRECTORY_SEPARATOR;
$diretorio = 'imagens' . DIRECTORY_SEPARATOR;
$diretorio2 = 'imagens' . DIRECTORY_SEPARATOR . 'tratadas' . DIRECTORY_SEPARATOR;

if(!is_dir($diretorio2)){
  mkdir($diretorio2,0777);
}

$dh =  opendir($diretorio);
$marca = imagecreatefrompng('./selo_fotos_flat.png');
list($width_m, $height_m) = getimagesize('./selo_fotos_flat.png');
$proporcao = $height_m/$width_m;
while (false !== ($filename = readdir($dh))) {
  if ($filename != '.' && $filename != '..' && $filename != 'tratadas' && substr(pathinfo($filename, PATHINFO_FILENAME),-3) != '_tb') {
    $name = limpaName(strtolower(pathinfo($filename, PATHINFO_FILENAME)));

    //se for HEIC converte
    $fileIsHeic = Maestroerror\HeicToJpg::isHeic($diretorio.$filename);
    if ($fileIsHeic) {
      Maestroerror\HeicToJpg::convert($diretorio.$filename)->saveAs( $diretorio.$name.".jpg");
      unlink($diretorio.$filename);
      $ext = 'jpg';
      $filename = $name.".jpg";
    } else {
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    $image = imagecreatefromjpeg($diretorio.$filename);
    $exif = @exif_read_data($diretorio.$filename);
    chmod($diretorio.$filename, 0777);
    //precisa fazer isso se não o windows não aceita a mudança
    rename(($root.$diretorio.$filename), ($root.$diretorio.'1'.$name.'.jpg'));
    rename(($root.$diretorio.'1'.$name.'.jpg'), ($root.$diretorio.$name.'.jpg'));
    //die($root.$diretorio.$filename .' - '. $root.$diretorio.$name.'.jpg');

    //rotaciona a imagem, se necessário
    if(!empty($exif['Orientation'])) {  
      switch ($exif['Orientation']) {
        case 3:
            $image = imagerotate($image, 180, 0);
            break;
        case 6:
            $image = imagerotate($image, -90, 0);
            break;
        case 8:
            $image = imagerotate($image, 90, 0);
            break;
      }
    }

    imagejpeg($image, $diretorio.($name).'.jpg', 100);
    $width = imagesx($image);
    $height = imagesy($image);

    //correção de cor
    imagefilter($image, IMG_FILTER_BRIGHTNESS,10);
    imagefilter($image, IMG_FILTER_CONTRAST,-10);
    imagefilter($image,  IMG_FILTER_COLORIZE,5,5,0);
    
    if($miniaturas) {
        //cria thumb
      $thumb = imagecreatetruecolor(400,400);
      $bg = imagecolorallocate($thumb, 255, 255, 255);
      imagefill($thumb, 0, 0, $bg);
      if($width > $height){
        $tb_h = (400*$height/$width);
        $tb_w = 400;
        $mt = (400 - $tb_h) / 2;
        $ms = 0;
      } elseif($width < $height){
        $tb_w = (400*$width/$height);
        $tb_h = 400;
        $mt = 0;
        $ms = (400 - $tb_w) / 2;
      } else {
        $tb_h = 400;
        $tb_w = 400;
        $ms = 0;
        $mt = 0;
      }
      imagecopyresampled($thumb, $image, $ms, $mt, 0, 0, $tb_w, $tb_h, $width, $height);
      imagejpeg($thumb, $diretorio.($name).'_tb.jpg', 100);
    }
    
    if($marcaDagua){
      //coloca o selo
      if($width <= 1500){
        if($width > $height){
          $width_wm = $width * .25;
        } else {
          $width_wm = $width * .4;
        }
      } else {
        if($width > $height){
          $width_wm = $width * .25;
        } else {
          $width_wm = $width * .4;
        }
      }
      $height_wm = $width_wm*$proporcao;
      //posicionamento Y
      $py = $height*.1;
      if($height < 1500){
        if($py > 50){
          $py = 50;
        }
      } else {
        if($py > 150){
          $py = 150;
        }
      }
      $pos_y = ($height-($height_wm+$py));
      imagecopyresampled($image, $marca, ($width-$width_wm),$pos_y, 0, 0, $width_wm, $height_wm, $width_m, $height_m);
    }

    if($assinatura != ""){
      // cor do texto em preto
      $text_color = imagecolorallocate($image, 0, 0, 0);
      // tamanho da fonte
      $font_size = 15*$height/638;
      //die(' - '.$font_size);
      // fonte
      $font = 'arial.ttf';
      // borda na fonte
      $borda_cor = imagecolorallocate($image, 255, 255, 255); // cor branca
      $borda_largura = 1*$height/638;
      // posicionamento
      $largura_texto = imagettfbbox($font_size, 0, $font, $assinatura);
      $assinatura_x = ($width - ($largura_texto[2] - $largura_texto[0]) - (10*$height/638));
      if(isset($pos_y)){
        $assinatura_y = $pos_y + $height_wm + $font_size + 10;
      } else {
        $assinatura_y = $height*.95;
      }


      imagettftext($image, $font_size, 0, $assinatura_x - $borda_largura, $assinatura_y - $borda_largura, $borda_cor, $font, $assinatura);
      imagettftext($image, $font_size, 0, $assinatura_x + $borda_largura, $assinatura_y - $borda_largura, $borda_cor, $font, $assinatura);
      imagettftext($image, $font_size, 0, $assinatura_x - $borda_largura, $assinatura_y + $borda_largura, $borda_cor, $font, $assinatura);
      imagettftext($image, $font_size, 0, $assinatura_x + $borda_largura, $assinatura_y + $borda_largura, $borda_cor, $font, $assinatura);      
      imagettftext($image, $font_size, 0, $assinatura_x, $assinatura_y, $text_color, $font, $assinatura);

    }
    
    //salva a imgem na pasta e com nome correto
    imagejpeg($image, $diretorio2.($name).'.jpg', 100);

    if($miniaturas){
      //cria thumb
      $thumb = imagecreatetruecolor(400,400);
      $bg = imagecolorallocate($thumb, 255, 255, 255);
      imagefill($thumb, 0, 0, $bg);
      if($width > $height){
        $tb_h = (400*$height/$width);
        $tb_w = 400;
        $mt = (400 - $tb_h) / 2;
        $ms = 0;
      } elseif($width < $height){
        $tb_w = (400*$width/$height);
        $tb_h = 400;
        $mt = 0;
        $ms = (400 - $tb_w) / 2;
      } else {
        $tb_h = 400;
        $tb_w = 400;
        $ms = 0;
        $mt = 0;
      }
      imagecopyresampled($thumb, $image, $ms, $mt, 0, 0, $tb_w, $tb_h, $width, $height);
      imagejpeg($thumb, $diretorio2.($name).'_tb.jpg', 100);
    }

    //libera memória
    imagedestroy($image);
    if($miniaturas){
      imagedestroy($thumb);
    }
  }
}
imagedestroy($marca);
closedir($dh);

function limpaName($nome){
  $nome = preg_replace('/[^a-zA-Z0-9_-]/s','_',$nome);

  return $nome;
}


