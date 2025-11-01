🚀 Instagram Graph API Uploader (PHP)
====================================

📸 Profesyonel Instagram içerik yükleme aracı.
Bu proje, **Instagram Graph API** kullanarak resim ve video içeriklerini 
tekli veya CSV üzerinden toplu şekilde yüklemenizi sağlar.

---

🧠 Özellikler
-------------
✅ Instagram Graph API v21.0 desteği  
✅ Tekli veya CSV (type,url,caption) ile toplu yükleme  
✅ Otomatik token yenileme (short → long-lived token)  
✅ IG_USER_ID tespiti (Facebook Graph üzerinden)  
✅ Video yüklemede durum (processing) kontrolü  
✅ Hata yönetimi ve ayrıntılı terminal çıktısı  

---

📦 Kurulum
----------
1️⃣ Projeyi klonlayın:
git clone https://github.com/ebubekirbastama/instagram-graph-api-uploader-php.git

2️⃣ Gerekli PHP uzantılarını aktif edin:
- curl
- json
- mbstring

3️⃣ API bilgilerinizi `ayarlar.txt` dosyasına girin: <br>
app_id=YOUR_APP_ID <br>
app_secret=YOUR_APP_SECRET <br>
short_token=YOUR_SHORT_TOKEN <br>
access_token=YOUR_LONG_LIVED_TOKEN (opsiyonel) <br>

4️⃣ Test edin:
php examples/single_upload_example.php

---

📚 Kullanım
-----------
### 🔸 Tekli Yükleme
require_once "src/InstagramUploader.php";

$bot = new InstagramUploader();
$bot->APP_ID       = "123456789";
$bot->APP_SECRET   = "YOUR_APP_SECRET";
$bot->SHORT_TOKEN  = "YOUR_SHORT_TOKEN";

$bot->refreshTokenIfNeeded();
$bot->uploadSingle("image", "https://example.com/image.jpg", "Merhaba Instagram!");

### 🔸 CSV'den Toplu Yükleme
$bot->uploadFromCSV("media_list_sample.csv");

CSV Formatı:
type,url,caption
image,https://example.com/photo1.jpg,Deneme fotoğrafı
video,https://example.com/video.mp4,Harika bir video!

---

⚙️ Örnek `ayarlar.txt`
app_id=4535343535
app_secret=54354354543354
short_token=EAAGm0PX4ZCpsBAJZBZA2...
access_token=
api_version=v21.0
poll_interval=5
timeout=600

---

🧪 Örnek Kullanım Senaryosu
----------------------------
📍 1. Adım → Facebook Developer hesabında uygulama oluşturun.  
📍 2. Adım → Instagram hesabınızı bu uygulamaya bağlayın.  
📍 3. Adım → Elde ettiğiniz kısa token'i `ayarlar.txt` içine ekleyin.  
📍 4. Adım → Komut satırından aşağıdaki örneği çalıştırın:

php examples/single_upload_example.php

💡 Not: Video yüklemelerinde işlem süresi 1–3 dakika arasında değişebilir.

---

🧑‍💻 Geliştirici
-----------------
👤 Ebubekir Bastama  
🌐 https://github.com/ebubekirbastama  


---

💡 Not
------
Bu araç **Facebook Developer App** üzerinden oluşturulan bir **Instagram Business/Creator hesabı** 
veya bağlı bir **Facebook Sayfası** gerektirir.

🔗 Resmî belgeler:  
https://developers.facebook.com/docs/instagram-api/

---

✨ İyi kodlamalar!  
📸 #InstagramGraphAPI #PHPUploader #Automation #SocialMediaTool
