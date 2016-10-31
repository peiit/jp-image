<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Libraries\ImageManipulator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;

class IndexController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        if (!file_exists($this->baseDir)) {
            mkdir($this->baseDir, 0777, true);
            chmod($this->baseDir, 0777);
        }
        
        //tmp 
        if (!file_exists($this->baseDir.'/tmp')) {
            mkdir($this->baseDir.'/tmp', 0777, true);
            chmod($this->baseDir.'/tmp', 0777);
        }
    }
    
    public function index(Request $request)
    {
        echo 'hello world';
    }

    function import(Request $request)
    {
        $tmpDir = $this->baseDir . '/' . 'tmp';
        $fileName = uniqid();
        $filePath = $tmpDir . '/' . $fileName;
        $src = $request->get('src');
        $dir = $request->get('module', '');

        if (!empty($src)) {
            $ch = curl_init($src);
            $exts = ["png", "jpg", "jpeg", "gif", "bmp"];
            $fp = fopen($filePath, "w");

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36",
            ));

            curl_exec($ch);

            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            $url_ext = substr(strrchr($src, '.'), 1);
            $mime_type = strstr($content_type, '/', true);
            $file_type = substr(strrchr($content_type, '/'), 1);
            $file_ext = '';

            if (
                $mime_type && 
                $mime_type == 'image' && 
                $file_type && 
                in_array($file_type, $exts)) {
                $file_ext = $file_type;
            }
            else if(in_array($url_ext, $exts)) {
                $file_ext = $url_ext;
            }

            if (empty($file_ext)) {
                return $this->json([], 3);
            }

            curl_close($ch);
            fclose($fp);

            $md5 = md5_file($filePath);
            $realDir = $this->baseDir . '/' . ($dir != ''? $dir.'/':'') . $md5;

            if (!file_exists($realDir)) {
                mkdir($realDir, 0777, true);
                chmod($realDir, 0777);
            }

            $realFileName = $md5 . '.' . $file_ext;
            $realFile = $realDir . '/' . $realFileName;
            rename($filePath, $realFile);
            $dimension = getimagesize($realFile);

            return $this->json([
                'id' => $realFileName,
                'w' => $dimension[0],
                'h' => $dimension[1],
                'full' => $dir != ''? $dir.'/'.$realFileName:$realFileName,
            ]);
        }
        else {
            return $this->json([], 4);
        }
    }

    public function upload(Request $request)
    {
        $file = Input::file('file');
        $dir = $request->get('module', '');
        $maxSize = config('constants.max_size');
        $baseDir = $this->baseDir;
        $tmpDir = $baseDir . '/' . 'tmp';
        
        //检查是否携带图片
        if (empty($file)){
            return $this->json(['msg' => 'file为空'], 5);
        }
        
        $ext = $file->getClientOriginalExtension();
        $fileName = uniqid();

        $size = $file->getSize();
        $exts = config('constants.exts');

        if ($file->isValid()) {
            if ($size > $maxSize) {
                return $this->json([], 1);
            }

            if (!in_array($ext, $exts)) {
                return $this->json([], 2);
            }

            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0777, true);
                chmod($tmpDir, 0777);
            }

            $rlt = $file->move($tmpDir, $fileName);

            if ($rlt) {
                $md5 = md5_file($tmpDir . '/' . $fileName);
                $realDir = $baseDir . '/' . ($dir != ''? $dir.'/':'') . $md5;

                if (!file_exists($realDir)) {
                    mkdir($realDir, 0777, true);
                    chmod($realDir, 0777);
                }

                $realFileName = $md5 . '.' . $ext;
                $realFile = $realDir . '/' . $realFileName;
                rename($tmpDir . '/' . $fileName, $realFile);
                $dimension = getimagesize($realFile);

                return $this->json([
                    'id' => $realFileName,
                    'w' => $dimension[0],
                    'h' => $dimension[1],
                    'full' => $dir != ''? $dir.'/'.$realFileName:$realFileName,
                ]);
            } else {
                return $this->json([], 3);
            }
        } else {
            return $this->json([], 4);
        }
    }

    /**
     * @param $id       图片ID
     * @param $w        目标宽度（如果只传入宽度则高度为等比缩放）
     * @param $h        目标高度
     * @param $mode
     *  1 - contain
     *  2 - cover
     * @param $module   模块名称
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \app\Libraries\RuntimeException
     */
    public function resizeWithModule($w, $h, $mode, $module, $id)
    {
        return $this->resize($w, $h, $mode, $id, $module);
    }

    public function resize($w, $h, $mode, $id, $module = null)
    {
        try{
            $ext = $this->getFileExtension($id);
            
            $savaImageType = IMAGETYPE_JPEG;
            if ($ext === 'png'){
                $savaImageType = IMAGETYPE_PNG;
            }else if($ext === 'gif'){
                $savaImageType = IMAGETYPE_GIF;
            }
            
            $imgDir = preg_replace('/\..+$/', '', $id);
            $imgPath = $this->baseDir . '/' . ($module? $module .'/':'') . $imgDir . '/';
            $imgFile = $imgPath . $id;
            $dimension = getimagesize($imgFile);
            $width = $dimension[0];
            $height = $dimension[1];
            $saveFile = $imgPath . 'r_' . $w . '_' . $h . '_' . $mode . '_' . $id;

            if (!file_exists($saveFile)) {
                $r = $w / $h;
                $ratio = $width / $height;

                if ($mode == 1) {
                    if ($r >= $ratio) {
                        $targetWidth = $width * $h / $height;
                        $targetHeight = $h;
                    } else {
                        $targetHeight = $height * $w / $width;
                        $targetWidth = $w;
                    }
                    $manipulator = new ImageManipulator($imgFile);
                    $manipulator->resample($targetWidth, $targetHeight);
                    $manipulator->save($saveFile, $savaImageType);
                }
                else if($mode == 2) {
                    if ($r < $ratio) {
                        $targetWidth = $width * $h / $height;
                        $targetHeight = $h;
                    } else {
                        $targetHeight = $height * $w / $width;
                        $targetWidth = $w;
                    }
                    $manipulator = new ImageManipulator($imgFile);
                    $manipulator->resample($targetWidth, $targetHeight);
                    // 从左上角裁剪
                    $manipulator->crop(0, 0, $w, $h);
                    $manipulator->save($saveFile, $savaImageType);
                }
            }

            $fp = fopen($saveFile, 'rb');

            $seconds_to_cache = 290304000;
            $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
            header("Expires: $ts");
            header("Pragma: cache");
            header("Cache-Control: max-age=$seconds_to_cache, public");
            header('Content-Type: image/' . $ext);
            header("Content-Length: " . filesize($saveFile));
            fpassthru($fp);
            // 不加 die 时返回小图片会有问题
            die();
        }
        catch(\Exception $exception) {
            return response('', 404);
        }
    }

    /**
     * 基于原始裁剪图片
     * @param $x            目标图片左上角起始点 x 坐标
     * @param $y            目标图片左上角起始点 y 坐标
     * @param $w            目标图片宽度
     * @param $h            目标图片高度
     * @param $module       目标图片模块名
     * @param $id   图片ID
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * @throws \app\Libraries\RuntimeException
     */
    public function cropWithModule($x, $y, $w, $h, $module, $id)
    {
        return $this->crop($x, $y, $w, $h, $id, $module);
    }

    public function crop($x, $y, $w, $h, $id, $module = null)
    {
        try{
            $ext = $this->getFileExtension($id);
            
            $savaImageType = IMAGETYPE_JPEG;
            if ($ext === 'png'){
                $savaImageType = IMAGETYPE_PNG;
            }else if($ext === 'gif'){
                $savaImageType = IMAGETYPE_GIF;
            }
            
            $imgDir = preg_replace('/\..+$/', '', $id);
            $imgPath = $this->baseDir . '/' . ($module? $module .'/':'') . $imgDir . '/';
            $imgFile = $imgPath . $id;
            $saveFile = $imgPath . 'c_' . $x . '_' . $y . '_' . $w . '_' . $h . '_' . $id;

            if (!file_exists($saveFile)) {
                $manipulator = new ImageManipulator($imgFile);
                $manipulator->crop($x, $y, $x + $w, $y + $h);
                if ($module == 'avatar') {
                    $manipulator->resample(100, 100);
                }
                $manipulator->save($saveFile, $savaImageType);
            }

            $fp = fopen($saveFile, 'rb');

            $seconds_to_cache = 290304000;
            $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
            header("Expires: $ts");
            header("Pragma: cache");
            header("Cache-Control: max-age=$seconds_to_cache, public");
            header('Content-Type: image/' . $ext);
            header("Content-Length: " . filesize($saveFile));
            fpassthru($fp);
            // 不加 die 时返回小图片会有问题
            die();
        }
        catch(\Exception $exception) {
            return response('', 404);
        }
    }
    
    /**
     * 获取文件后缀
     * @param unknown $path
     */
    private function getFileExtension($path){
        return pathinfo($path)['extension'];
    }
}
