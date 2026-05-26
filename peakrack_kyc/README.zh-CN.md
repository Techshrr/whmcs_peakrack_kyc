# PeakRack KYC 模块目录

此目录是可部署的 WHMCS Addon Module。

安装路径：

```text
modules/addons/peakrack_kyc/
```

主要文件：

- `peakrack_kyc.php`：插件生命周期、后台 UI 和客户前台处理。
- `hooks.php`：下单拦截、开通拦截、下单后记录和定时清理。
- `lib/Bootstrap.php`：设置、存储、实名状态、规则 CRUD、API Provider、上传、日志、邮件模板和强制实名逻辑。
- `lib/Providers/`：Provider 统一接口、腾讯云三要素、支付宝实名信息、支付宝人脸、银行卡要素、企业要素、海外 KYC、人工审核和预留 Provider 适配器。
- `templates/clientarea.tpl`：兼容 Lagom / Bootstrap 的客户实名页面。
- `lang/`：WHMCS 插件语言文件。
- `database/`：安装建表 SQL 和可选清理 SQL，便于部署前核对。

不要把客户上传的实名证件放在模块目录内。请使用后台配置的私有存储路径，并优先配置到网站根目录之外。
后台存储选择器可以保存 S3/S3 兼容配置，便于后续启用对象存储适配器；当前版本的上传和下载仍使用本地私有路径。

后台证件下载必须经过插件控制器、WHMCS 管理员会话和 token 校验。客户前台只能查看文件名、状态和上传时间，不能下载原始文件；客户可删除自己未通过或待审核的上传材料。

后台 Email Notifications 区域可安装默认 WHMCS 邮件模板，并自动映射提交成功、审核通过、审核驳回三类通知。
内置模板包含中英双语客户通知内容和实名变量；只有管理员勾选刷新选项时才会覆盖已存在的 PeakRack 默认模板。

v1.2 开发分支保留 Provider 框架，并新增支付宝 OAuth state 数据库存储，避免授权回调只依赖 PHP session。
当前高级 Provider 已覆盖支付宝人脸、银行卡要素、企业要素、法人人脸和可配置海外 KYC API；银行卡和企业核验可在后台切换腾讯云或阿里云市场 AppCode 通道。后台默认只展示当前通道必填项，字段映射和响应判断放在高级区域。

## 许可证

此可部署模块目录内已包含 [LICENSE](LICENSE) 和 [NOTICE](NOTICE)。

授权、续期、再分发或书面许可请求请发送至 `legal@peakrack.com`。
