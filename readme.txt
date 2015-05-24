=== WP2PCS-SY （WordPress连接到网盘） ===
Contributors: wishinlife
Donate link: http://www.syncy.cn/index.php/donate/
Tags:wp2pcs-sy, backup, sync, baidu, personal cloud storage, PCS, 百度网盘
Requires at least: 3.5.1
Tested up to: 4.2.2
Stable tag: 1.3.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

备份WordPress到网盘，把PCS作为网站附件存储空间。

== Description ==

WP2PCS-SY是基于WP2PCS插件修改而来，在原版本的基础上取消了外链，另增加了新的功能，并做了完善， 主要功能就是把WordPress和网盘（PCS，个人云存储）连接在一起的插件。它的两项基本功能就是：将wordpress的数据库、文件<strong>备份</strong>到网盘，以防止由于过失而丢失了网站数据；把网盘作为网站的后备箱，<strong>存放</strong>图片、附件，解决网站空间不够用的烦恼，这个时候，你可以在网站内<strong>直接引用</strong>网盘上的文件，并提高你的网站SEO和用户体验，并具有防盗链功能。
WP2PCS-SY将你的WordPress定时备份到百度网盘，把百度网盘作为附件存储空间，解决你的网站后顾之忧。

WP2PCS官方网站 http://www.wp2pcs.com <br />
WP2PCS-SY官方网站：http://www.syncy.cn

<strong>修改内容如下：</strong><br />
1、修改了授权模式，采用自有APIkey的时候不会再向第三方网站传输APIkey和securtkey，直接和百度服务器通信，减少了泄露Securtkey的风险；使用wp2pcs-sy的APIkey的话刷新码也存储在本地（wp2pcs-sy承诺永不存储用户的refreshtoken和accesstoken），并定期刷新accesstoken，不用再手动刷新accesstoken；<br />
2、在多媒体下面增加了一个百度网盘的菜单，可以浏览百度网盘中上传目录下的文件，不用再到编辑文章页面才可以浏览到图片等，同时也可以通过此页面上传单个文件；<br />
3、多媒体-百度网盘和编辑文章插入图片的页面显示的图片全部是图片的缩略图（原版本中获取的是完整图片文件），加快了图片的浏览；<br />
4、增加了浏览网盘文件时的排序功能，可按修改时间倒排或顺排、按文件名倒排或顺排；<br />
5、增加了文件名对特殊字符及空格的支持，文件名可支持除PCS规定不能使用的字符外的所有字符；<br />
6、取消了外链功能，采用直链也不存在泄露accesstoken的风险；<br />
7、增加了普通文件、mp3、通用媒体文件的缓存功能；<br />
8、增加了对缓存文件清理的功能；<br />
9、增加了防盗链功能；<br />
10、优化了数据库备份功能，原版本中在BAE上备份很难成功，优化后很少失败；<br />
11、修复了原版本中下载文件失败的bug；<br />
12、优化了在加载下一页图片时失败，导致下一页按钮不可见的问题；<br />
13、所有功能免费开放。<br />


<strong>不适用范围</strong>

* 超大型网站（打包后超过G）
* 开启MULTISITE的多站点网站
* 网站空间剩余不足三分之一
* 没有读写权限或读写权限受限制的空间（如BAE、SAE不能备份网站文件）
* 服务器memory limit, time limit比较小，又不能自己修改的
* 主机PHP不支持ZipArchive类
* 免费主机、海外主机等性能差或与PCS通信弱的主机

== Installation ==

1、把wp2pcs-sy文件夹上传到/wp-content/plugins/目录<br />
2、在后台插件列表中激活它<br />
3、在“插件-WP2PCS-SY”菜单中，点击授权按钮，等待授权跳转<br />
在授权过程中，如果你已经登录了百度账号，会直接跳转；如果没有登录百度账号，会要求你登录，登录之后一定要勾选同意授权网盘（PCS）服务，否则无法使用插件中的服务。<br />
4、如果授权成功，你会进入到插件的使用页面。<br />
5、初始化所有信息。<br />
6、如果不能正常访问网盘，点击重新授权按钮重新授权。<br />
7、如果在BAE上备份不成功，可修改wordpress根目录下的wp-cron.php，在文件开头增加语句“set_time_limit(0);”看能否正常备份。

== Frequently Asked Questions ==

* 1.当发现插件出错时，开启调试获取错误信息。当插件运行缓慢或通信不良时，开启“简易加速”。
* 2.定时备份，在规定的时间，将网站打包备份到网盘；立即压缩下载备份的压缩文件。
* 3.增量备份只备份你修改或上传的文件，没有变化的文件不上传。
* 4.设置附件访问的前缀，通过特定的URL就可以访问网盘中的附件资源。
* 5.在文章编辑页面“添加媒体”按钮后的媒体管理面板中选择插入网盘中的附件。

== Screenshots ==

screenshot-1.jpg
screenshot-2.jpg
screenshot-3.jpg
screenshot-4.jpg
screenshot-5.jpg

== Changelog ==

= 1.3.12 =
* 1、修改服务器网址，所有服务迁移至自有域名www.syncy.cn上。

= 1.3.11 =
* 1、修正了视频播放支持移动设备，如ipad、iphone、安卓手机等。

= 1.3.10 =
* 1、修复了视频文件被缓存后一段时间无法访问的问题，同时因缓存的是视频播放列表文件存在访问失效，取消m3u8文件的缓存。

= 1.3.9 =
* 1、修复了audio和video文件的中文支持问题。
* 2、修复了开启防盗链后无法访问视频的问题。

= 1.3.8 =
* wp2pcs-sy是基于wp2pcs 1.3.8版本修改而来，主要修改内容如下：
* 1、修改了授权模式，采用自有APIkey的时候不会再向第三方网站传输APIkey和securtkey，直接和百度服务器通信，减少了泄露Securtkey的风险；使用wp2pcs-sy的APIkey的话刷新码也存储在本地（wp2pcs-sy承诺永不存储用户的refreshtoken和accesstoken），并定期刷新accesstoken，不用再手动刷新accesstoken；
* 2、在多媒体下面增加了一个百度网盘的菜单，可以浏览百度网盘中上传目录下的文件，不用再到编辑文章页面才可以浏览到图片等，同时也可以通过此页面上传单个文件；
* 3、多媒体-百度网盘和编辑文章插入图片的页面显示的图片全部是图片的缩略图（原版本中获取的是完整图片文件），加快了图片的浏览；
* 4、增加了浏览网盘文件时的排序功能，可按修改时间倒排或顺排、按文件名倒排或顺排；
* 5、增加了文件名对特殊字符及空格的支持，文件名可支持除PCS规定不能使用的字符外的所有字符；
* 6、取消了外链功能，采用直链也不存在泄露accesstoken的风险；
* 7、增加了普通文件、mp3、通用媒体文件的缓存功能；
* 8、增加了对缓存文件清理的功能；
* 9、增加了防盗链功能；
* 10、优化了数据库备份功能，原版本中在BAE上备份很难成功，优化后很少失败；
* 11、修复了原版本中下载文件失败的bug；
* 12、优化了在加载下一页图片时失败，导致下一页按钮不可见的问题；
* 13、所有功能免费开放。

== Upgrade Notice ==

如果你之前用的有wp2pcs，请先禁用wp2pcs，然后在启用wp2pcs-sy，这两个不能同时为启用状态。