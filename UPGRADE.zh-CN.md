# 升级说明

## 1.2.0-dev

- 新增 `mod_peakrack_kyc_oauth_states`，用于持久化支付宝 OAuth 授权回调 state。
- 覆盖上传后打开一次插件后台页面，`peakrackKycCreateTables()` 会自动创建新表。
- 如果生产环境采用手动 SQL 变更，请先执行 `database/mysql.sql`，再启用支付宝实名信息验证。
- 新增 S3/S3 兼容存储配置入口，便于后续启用对象存储适配器；当前版本上传和下载仍使用本地私有存储。
- 新增支付宝人脸、银行卡要素、企业要素、法人人脸和海外 KYC API 高级 Provider。生产启用前请先完成密钥、字段映射和测试模式/沙箱验证。

## 1.0.0

首次发布。

### 全新安装

上传：

```text
peakrack_kyc/ -> modules/addons/peakrack_kyc/
```

然后在 `System Settings > Addon Modules` 启用 **PeakRack KYC**。

### 升级步骤

1. 如果已有 `modules/addons/peakrack_kyc/`，先备份该目录。
2. 用新版 `peakrack_kyc/` 覆盖旧目录。
3. 在 WHMCS 后台打开一次插件页面，让数据表检查和默认设置自动执行。
4. 检查 API 设置、强制实名产品范围和私有存储路径。
5. 如需启用银行卡或企业核验，选择腾讯云或阿里云市场 AppCode 通道；阿里云市场商品字段和响应结构不统一，需配置字段映射和成功 JSON 路径/值。
6. 如选择 `S3 / S3-compatible`，仍需保留本地私有存储路径；当前版本只是保存 S3 配置，实际上传下载仍走本地私有存储。
7. 发布到生产环境前，对插件 PHP 文件运行 `php -l`。

### 数据安全

停用插件不会删除实名资料、证件记录、日志和 API 调用记录。除非已经确认不再需要对应证件记录，否则不要删除私有上传目录。
