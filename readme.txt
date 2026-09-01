=== HaloPress-Gate ===
Contributors: YumengOvO
Tags: age gate, content warning, animation, adult content
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

可配置的成人内容确认层与图片预加载开场动画。

== Description ==

HaloPress-Gate 在网站正文上方显示全屏内容确认层。访客确认后，两层色块会依次收起并露出页面。

功能：

* 图片预加载进度动画
* 可修改提示文字、按钮、跳转网址和三种颜色
* 可使用自定义图标
* Cookie 记忆确认结果
* 可选择是否向回访者继续播放开场动画
* 键盘焦点循环和 reduced-motion 支持
* 不依赖 jQuery 或第三方动画库
* 提供 `acg_should_show_gate` 过滤器和 `acg:entered` 浏览器事件

== Installation ==

1. 将 `halopress-gate` 文件夹上传到 `/wp-content/plugins/`。
2. 在 WordPress 后台“插件”页面启用 HaloPress-Gate。
3. 打开“设置 → 内容确认动画”完成配置。

== Frequently Asked Questions ==

= 如何只在部分页面显示？ =

可以在主题或自定义插件中使用 `acg_should_show_gate` 过滤器：

`add_filter( 'acg_should_show_gate', function () { return is_front_page(); } );`

= 如何在动画结束后启动自己的页面动画？ =

监听 `acg:entered` 事件，或者使用添加到 `html` 和 `body` 的 `acg-entered` 类。

== Changelog ==

= 1.0.1 =

* 确认后立即隐藏提示文字，再播放幕布退场动画。
* 在插件列表加入“设置”入口。
* 补充作者与插件主页信息。

= 1.0.0 =

* 初始版本。
