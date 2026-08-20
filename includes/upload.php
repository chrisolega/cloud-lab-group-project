<?php
function save_image($file) {
if (!isset($file) || $file['error'] != 0) {
return false;
}
$allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
return false;
}
if ($file['size'] > 5 * 1024 * 1024) {
return false;
}
$name = time() . '_' . rand(1000, 9999) . '.' . $ext;
if (UPLOAD_MODE == 's3') {
require_once __DIR__ . '/../vendor/autoload.php';
$s3 = new Aws\S3\S3Client(array(
'version' => 'latest',
'region' => S3_REGION
));
$result = $s3->putObject(array(
'Bucket' => S3_BUCKET,
'Key' => 'events/' . $name,
'SourceFile' => $file['tmp_name'],
'ContentType' => $file['type']
));
return $result['ObjectURL'];
} else {
move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/' . $name);
return $name;
}
}
function pic($p) {
if (strpos($p, 'http') === 0) {
return $p;
}
return '../uploads/' . $p;
}
