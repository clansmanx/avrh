<?php
// create_default_avatar.php
$im = imagecreate(150, 150);
$bg = imagecolorallocate($im, 200, 200, 200);
$text_color = imagecolorallocate($im, 100, 100, 100);
imagestring($im, 5, 50, 65, "Sem Foto", $text_color);
imagepng($im, 'assets/img/default-avatar.png');
imagedestroy($im);
echo "Avatar padrão criado!";
?>
