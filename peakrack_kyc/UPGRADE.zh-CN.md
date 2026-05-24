# 模块升级说明

将此目录上传到 `modules/addons/peakrack_kyc/`，然后在 WHMCS 后台打开插件页面，让数据表检查自动执行。

1.2.0-dev 新增 `mod_peakrack_kyc_oauth_states`，用于持久化支付宝 OAuth 授权回调 state。打开插件后台页面或执行升级钩子会自动创建该表。

升级时请保留已配置的私有存储目录和数据库表。
