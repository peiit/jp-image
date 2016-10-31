# 图片服务器使用指南

## 域名

上传：http://domain.com
访问：http://domain-cdn.com （无 cookie）

## 图片上传

> POST /upload

请求参数：

 * file: 文件
 * module: [可选] 文件上传的模块名，将会已此模块名为名建立独立文件夹。（推荐添加）
 
 约定：C 端的头像 module 为 avatar

响应内容：

```js
{
  "error_code": 0,
  "data": {
    "id": "9cd81f49b5de690e158ec30b95ded590.png",
    "w": 1024,
    "h": 768
  }
}
```

 * `error_code` 状态码
    * 1 - 图片超过 20M
    * 2 - 图片格式不支持
    * 3 - 系统失败
    * 4 - 上传文件无效

 * `id` 图片在服务器上的 ID，此 ID 会用于图片显示
 * `w`  图片宽度
 * `h`  图片高度

根据图片ID可以直接通过以下路径访问原图

> GET /{id}

例如

> http://domain-cdn.com/9cd81f49b5de690e158ec30b95ded590.png

如果需要其他尺寸的图片请参考以下接口。

## 图片导入

> GET /import
下载指定 URL 的图片到校联帮图片服务器上并返回图片 ID

请求参数：

 * src: 图片 URL
 * module: [可选] 文件上传的模块名，将会已此模块名为名建立独立文件夹。（推荐添加）
 
 约定：C 端的头像 module 为 avatar

响应内容：

```js
{
  "error_code": 0,
  "data": {
    "id": "9cd81f49b5de690e158ec30b95ded590.png",
    "w": 1024,
    "h": 768
  }
}
```

 * `error_code` 状态码
    * 3 - 系统失败
    * 4 - 上传文件无效

 * `id` 图片在服务器上的 ID，此 ID 会用于图片显示
 * `w`  图片宽度
 * `h`  图片高度

根据图片ID可以直接通过以下路径访问原图

> GET /{id}

例如

> http://domain-cdn.com/9cd81f49b5de690e158ec30b95ded590.png

如果需要其他尺寸的图片请参考以下接口。

## 图片缩放

> GET /resize/{width}/{height}/{mode}/{module?}/{pid}

请求参数：

 * `width`:   缩放目标宽度
 * `height`:  缩放目标高度
 * `mode`:    缩放策略
    * 1 - 图片不会被裁剪，在目标高宽矩形中始终展示完全(contain)
    * 2 - 图片始终会充满整个目标矩形，超出部分会被裁剪掉(cover)
 * `pid`:     缩放来源图片 ID
 * `module`:  图片所在模块

响应内容：
    图片文件

例如：
> http://domain-cdn.com/resize/50/50/1/9cd81f49b5de690e158ec30b95ded590.png

## 图片裁剪

> GET /crop/{x}/{y}/{width}/{height}/{module?}/{pid}

请求参数：

 * `x`:       裁剪起始位置 x
 * `y`:       裁剪起始位置 y
 * `width`:   裁剪目标宽度
 * `height`:  裁剪目标高度
 * `pid`:     缩放来源图片 ID
 * `module`:  图片所在模块

响应内容：
    图片文件

例如：
> http://domain-cdn.com/crop/200/200/300/300/9cd81f49b5de690e158ec30b95ded590.png


##Nginx配置
```nginx
# access original image
rewrite ^/(\w+/)?(\w+)\.(jpg|gif|png|jpeg)$ /uimgs/$1$2/$2.$3;

# try to read cached file firstly
rewrite ^/crop/(\d+)/(\d+)/(\d+)/(\d+)/(\w+/)?(\w+)\.(jpg|gif|png|jpeg)$ /uimgs/$5$6/c_$1_$2_$3_$4_$6.$7 last;

# try to read cached file firstly
rewrite ^/resize/(\d+)/(\d+)/(\d+)/(.+?)\.(jpg|gif|png|jpeg)$ /uimgs/$4/r_$1_$2_$3_$4.$5 last;
```