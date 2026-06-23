# 玉里協天宮 官方網站

花蓮縣玉里鎮，清光緒元年（1875）創建，主祀關聖帝君。

---

## 技術棧

| 項目 | 技術 |
|------|------|
| 前台 | 純 HTML5 + CSS3 + Vanilla JS |
| 字型 | Google Fonts（Noto Serif TC、Cormorant Garamond） |
| 滾動動畫 | AOS.js 2.3.4 |
| 消息資料 | 本地 `news.json` |
| 後台管理 | PHP 8（Session 驗證） |
| 部署 | Apache 2.4 on Linode Ubuntu |

---

## 檔案結構

```
temple-site/
├── index.html              # 首頁（單頁設計）
├── news.html               # 活動消息詳細頁
├── assets/
│   ├── css/
│   │   ├── reset.css       # CSS reset
│   │   ├── variables.css   # 色彩、字型、間距變數
│   │   ├── main.css        # 主要樣式
│   │   └── news-detail.css # 消息詳細頁樣式
│   ├── js/
│   │   ├── main.js         # 載入動畫、導覽、Ken Burns 輪播
│   │   └── news.js         # 讀取 news.json 並渲染消息列表
│   ├── data/
│   │   └── news.json       # 活動消息資料
│   └── img/
│       ├── hero1.jpg       # Hero 輪播圖 1（桌機 1920×1080）
│       ├── hero2.jpg       # Hero 輪播圖 2
│       ├── hero3.jpg       # Hero 輪播圖 3
│       ├── hero4.jpg       # Hero 輪播圖 4
│       ├── hero5.jpg       # Hero 輪播圖 5
│       ├── hero1-m.jpg     # Hero 輪播圖 1（手機直式 750×1334）
│       ├── hero2-m.jpg     # Hero 輪播圖 2（手機直式）
│       ├── hero3-m.jpg     # Hero 輪播圖 3（手機直式）
│       ├── hero4-m.jpg     # Hero 輪播圖 4（手機直式）
│       ├── hero5-m.jpg     # Hero 輪播圖 5（手機直式）
│       ├── about.jpg       # 廟宇介紹區圖片
│       └── deity.jpg       # 主神介紹圖片
└── admin/
    ├── index.php           # 後台登入 + 管理介面
    ├── save.php            # 新增／刪除消息的 POST handler
    └── config.php          # 密碼設定（不上傳 Git）
```

---

## 本機開發

需要安裝 PHP 8+（macOS 內建或 Homebrew）。

```bash
cd temple-site
php -S localhost:8787
```

開啟瀏覽器：
- 前台：`http://localhost:8787`
- 後台：`http://localhost:8787/admin/`

---

## 活動消息後台

### 登入

網址：`https://廟名.com/admin/`（或本機 `http://localhost:8787/admin/`）

預設密碼：`temple1875`（**部署後請立即修改**）

### 新增消息

填入 **標題**、**日期**、選填**詳細內文**和**外部連結**，按「新增消息」。

- 詳細內文空一行（兩次 Enter）= 新段落
- 填了外部連結，消息詳細頁底部會顯示「查看更多詳情」按鈕

### 修改密碼

```bash
# 在伺服器上執行
php -r "echo password_hash('你的新密碼', PASSWORD_BCRYPT);"
```

把輸出的 hash 貼入 `admin/config.php`：

```php
define('ADMIN_PASSWORD_HASH', '輸出的hash');
```

---

## 部署（Linode Ubuntu）

伺服器 IP：`172.238.14.15`

### 首次部署

```bash
# 在本機執行，上傳全部檔案
rsync -avz --exclude='.DS_Store' --exclude='.git' \
  temple-site/ root@172.238.14.15:/var/www/temple-site/
```

#### 伺服器上執行

```bash
# 安裝套件
apt update && apt install -y apache2 php libapache2-mod-php php-json

# 設定寫入權限（PHP 需要能寫 news.json）
chown -R www-data:www-data /var/www/temple-site
chmod 775 /var/www/temple-site/assets/data
chmod 664 /var/www/temple-site/assets/data/news.json

# 建立 VirtualHost
nano /etc/apache2/sites-available/temple.conf
```

VirtualHost 內容（`/etc/apache2/sites-available/temple.conf`）：

```apache
<VirtualHost *:80>
    ServerName 廟名.com
    ServerAlias www.廟名.com
    DocumentRoot /var/www/temple-site

    <Directory /var/www/temple-site>
        Options -Indexes
        AllowOverride None
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/temple-error.log
    CustomLog ${APACHE_LOG_DIR}/temple-access.log combined
</VirtualHost>
```

```bash
a2dissite 000-default
a2ensite temple
systemctl reload apache2
```

### 申請免費 HTTPS（Let's Encrypt）

DNS 生效後執行：

```bash
apt install -y certbot python3-certbot-apache
certbot --apache -d 廟名.com -d www.廟名.com
```

選「Redirect」讓 HTTP 自動轉 HTTPS。憑證每 90 天自動更新。

### 更新程式碼

```bash
rsync -avz --exclude='.DS_Store' --exclude='.git' --exclude='admin/config.php' \
  temple-site/ root@172.238.14.15:/var/www/temple-site/
```

> `--exclude='admin/config.php'`：保留伺服器上已設好的密碼，不被覆蓋。

---

## 待甲方確認

- [x] 廟宇正式全名（玉里協天宮）
- [x] Hero 輪播照片（`hero1–5.jpg` 桌機、`hero1–5-m.jpg` 手機直式）
- [x] 廟宇介紹區照片（`about.jpg`）
- [x] 主神照片（`deity.jpg`）
- [x] 正確地址（花蓮縣玉里鎮民生街 52 巷 13 號）
- [ ] 聯絡電話
- [ ] Google Maps Embed URL（從 Google Maps 產生正確座標）
- [ ] 網域名稱確認
- [ ] 後台管理員密碼（部署後設定）
