# Animated Content Gate

一个可直接安装的 WordPress 内容确认插件。它借鉴目标网页的交互机制：图片加载进度填满彩色层，确认文字淡出后，彩色层与底色层依次向上收起。

## 安装

1. 将 `animated-content-gate` 文件夹压缩为 ZIP，或直接复制到 `wp-content/plugins/`。
2. 在 WordPress 后台启用插件。
3. 前往“设置 → 内容确认动画”。

## 扩展接口

只在首页显示：

```php
add_filter( 'acg_should_show_gate', function () {
    return is_front_page();
} );
```

在动画结束后运行前端代码：

```js
window.addEventListener('acg:entered', function () {
    // 启动主题自己的首屏动画。
});
```

插件未包含或复制目标网站的图像、字体、角色素材或代码库。
