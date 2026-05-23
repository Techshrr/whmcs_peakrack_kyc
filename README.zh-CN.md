# PeakRack KYC for WHMCS

[English](README.md) | [简体中文](README.zh-CN.md)

PeakRack KYC 是一个 WHMCS 实名认证插件，用于客户实名中心、证件上传、人工审核、腾讯云手机号三要素核验，以及按产品、产品组或 TLD 强制实名。

## 当前版本

`1.1.0-dev`

## 功能

- 支持 WHMCS 9.0.3 和 PHP 8.2 / 8.3。
- 提供客户前台实名认证中心。
- 提供管理员后台审核队列，支持通过、驳回、撤销和要求重新提交。
- 提供后台实名详情页，可查看文件、提交记录、Provider 日志和审计日志。
- 提供后台系统检查卡片，用于检查 PHP 扩展、私有存储、防直链文件和 API 密钥准备状态。
- 提供产品、产品组、TLD 实名规则的后台新增、编辑、启用/停用和删除。
- 支持腾讯云 FaceID `PhoneVerification` 手机号、姓名、身份证号三要素核验。
- 支持支付宝实名信息验证，流程为 OpenAPI V3 预咨询、支付宝用户授权、回调换取授权令牌、咨询核验结果。
- 已实现 `TencentPhoneThreeFactorProvider`、`AlipayRealNameInfoProvider` 和 `ManualReviewProvider`，并为后续 v1.1 路线预留 Provider 适配器。
- 支持个人、企业、海外护照、地址证明、营业执照、水电费账单等人工上传审核场景。
- 可在下单前拦截未实名客户，也可允许先下单但禁止自动开通。
- 实名驳回后可选择人工处理，或自动取消未付款 Pending 订单。
- 支持客户和管理员邮件通知，并可在后台安装默认 WHMCS 邮件模板。
- 默认客户邮件模板已扩展为中英双语内容，可显示实名状态、类型、方式、证件类型、国家、审核原因和实名中心链接。
- 已存在的 PeakRack 默认模板只有在管理员勾选刷新选项时才会被覆盖。
- 后台支持手动执行保留清理，用于清理旧审计日志、API 日志，以及已经标记删除且超过保留期的文件记录和物理文件。
- 上传文件使用私有目录、随机文件名、扩展名校验、MIME 校验和文件头校验。
- 后台下载证件必须经过 WHMCS 管理员会话和 token 校验。
- 客户不能下载原始证件文件，但可删除自己未通过/待审核的上传材料。
- 敏感号码只保存加盐哈希和后四位展示值，不保存明文。
- 支持中英双语文案，使用 WHMCS 标准 Smarty 模板和 Bootstrap 兼容结构。

## 范围说明

`1.0.0` 已冻结在 `release/v1.0.0` 分支和 `v1.0.0` 标签。`develop/v1.1` 分支开始建设 Provider 框架，并已接入支付宝实名信息验证。支付宝人脸、银行卡多要素、法人人脸/企业核验、海外 KYC API 仍为预留 Provider，在真实核验逻辑完成前不能选择启用。

## 安装

将可部署插件目录复制到：

```text
modules/addons/peakrack_kyc/
```

然后在 WHMCS 后台打开：

```text
System Settings > Addon Modules
```

启用 **PeakRack KYC**，再进入：

```text
Addons > PeakRack KYC
```

配置 API、私有上传路径、强制实名模式，以及产品/产品组/TLD 规则。

## 强制实名规则

插件支持三种模式：

- `不强制`：不拦截下单或开通。
- `全部产品`：购物车内任意产品都要求实名。
- `仅指定产品`：只对后台规则中配置的产品、产品组或 TLD 强制实名。

规则在后台 **Product / Product Group / TLD Rules** 区域维护。开启下单前拦截时，`ShoppingCartValidateCheckout` 会在生成订单和账单前阻止未实名客户下单。允许先下单模式下，订单可以保持 Pending，但 `PreModuleCreate` 仍会阻止服务自动开通。

## API Provider

### 腾讯云三要素

用于腾讯云 `PhoneVerification`。需要配置 `Tencent SecretId`、`Tencent SecretKey`、Region、Endpoint 和 VerifyMode。

### 支付宝实名信息验证

用于支付宝实名信息比对。客户提交姓名和中国大陆身份证号码后，插件先调用 `preconsult` 获取 `verify_id`，再跳转支付宝授权；支付宝回调后，插件用 `auth_code` 换取 `access_token` 并调用 `consult`，根据结果自动标记通过或驳回。

需要配置：

- `Alipay AppID`
- `Alipay app private key`
- `Alipay OpenAPI base URL`，默认 `https://openapi.alipay.com`
- `Alipay authorization URL`，默认 `https://openauth.alipay.com/oauth2/publicAppAuthorize.htm`
- 将后台显示的回调地址加入支付宝应用授权回调白名单

## 文件安全

默认文件存储路径：

```text
attachments/peakrack_kyc_private/
```

插件会写入 `.htaccess`、`web.config` 和 `index.html` 降低直链风险。生产环境建议将 `Private storage path` 配置到网站根目录之外，并确认 PHP 运行用户有写入权限。

管理员下载证件只能通过插件控制器完成，并要求 WHMCS 管理员会话和有效 token。客户前台只显示文件名、状态和上传时间，不提供原始文件下载。

## 邮件模板

后台 Email Notifications 区域可以安装默认 WHMCS 邮件模板：

- `PeakRack KYC Submitted`
- `PeakRack KYC Approved`
- `PeakRack KYC Rejected`

安装器不会覆盖已有模板，并会自动写入对应模板配置。

## 数据表

插件创建：

- `mod_peakrack_kyc_settings`
- `mod_peakrack_kyc_profiles`
- `mod_peakrack_kyc_submissions`
- `mod_peakrack_kyc_documents`
- `mod_peakrack_kyc_provider_logs`
- `mod_peakrack_kyc_rules`
- `mod_peakrack_kyc_audit_logs`

停用插件不会删除数据表，以保留审计记录。迁移 SQL 位于 `database/mysql.sql` 和 `peakrack_kyc/database/mysql.sql`。

## 测试清单

- 在 WHMCS 9.0.3、PHP 8.2 / 8.3 下启用插件。
- 保存设置时 SecretKey 留空，确认已保存密钥保留且页面不回显。
- 使用测试模式提交腾讯云三要素核验。
- 使用测试模式提交支付宝实名信息验证。
- 在支付宝测试应用中配置授权回调白名单，实测 `preconsult -> 授权回调 -> token exchange -> consult`。
- 提交 JPG、PNG、PDF 人工实名材料。
- 确认扩展名、MIME 和文件头不匹配的文件会被拒绝。
- 后台执行通过、驳回、撤销、要求重新提交。
- 确认被驳回、撤销、过期的客户可以重新提交资料。
- 确认客户可删除未通过/待审核材料，已通过材料需管理员处理。
- 确认客户不能下载原始证件文件。
- 确认后台下载需要管理员会话和有效 token。
- 安装默认 WHMCS 邮件模板并确认模板配置已映射。
- 配置产品、产品组和 `cn` / `com.cn` 等 TLD 规则并测试下单拦截。
- 配置允许先下单模式，确认 `PreModuleCreate` 阻止自动开通。
- 驳回未付款 Pending 订单，确认可选取消策略生效。
- 确认已付款但未实名订单只通知管理员人工处理，不自动退款。
