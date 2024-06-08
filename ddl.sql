-- 傾印  資料表 mqtt-auth.acl 結構
CREATE TABLE IF NOT EXISTS `acl` (
  `username` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 取消選取資料匯出。

-- 傾印  資料表 mqtt-auth.auth 結構
CREATE TABLE IF NOT EXISTS `auth` (
  `username` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` char(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allow` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
