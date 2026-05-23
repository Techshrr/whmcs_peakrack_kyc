# 升级说明

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
5. 发布到生产环境前，对插件 PHP 文件运行 `php -l`。

### 数据安全

停用插件不会删除实名资料、证件记录、日志和 API 调用记录。除非已经确认不再需要对应证件记录，否则不要删除私有上传目录。
