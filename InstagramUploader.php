<?php
/**
 * EBS InstagramUploader – PHP Class
 * ------------------------------
 * - Tekli: resim/video + caption
 * - Toplu: CSV'den (type,url,caption)
 * - Video için processing durumu kontrol edilir
 * - Token süresi dolmuşsa otomatik yeniler
 * - IG_USER_ID otomatik alınır
 */

class InstagramUploader
{
    // ==========================
    //  Global Ayarlar
    // ==========================
    private $ACCESS_TOKEN  = "";
    private $IG_USER_ID    = "";
    private $APP_ID        = "";
    private $APP_SECRET    = "";
    private $SHORT_TOKEN   = "";
    private $API_VERSION   = "v21.0";
    private $POLL_INTERVAL = 5;
    private $TIMEOUT       = 600;
    private $GRAPH_BASE;

    public function __construct()
    {
        $this->GRAPH_BASE = "https://graph.facebook.com/" . $this->API_VERSION;
    }

    // ==========================
    //  Token Yenileme + IG_USER_ID Alma
    // ==========================
    public function refreshTokenIfNeeded()
    {
        if (empty($this->APP_ID) || empty($this->APP_SECRET)) {
            echo "[UYARI] app_id ve app_secret gerekli.\n";
            return;
        }

        $tokenToCheck = $this->ACCESS_TOKEN ?: $this->SHORT_TOKEN;
        if (empty($tokenToCheck)) {
            echo "[UYARI] access_token veya short_token bulunamadı.\n";
            return;
        }

        echo "[BİLGİ] Token kontrol ediliyor...\n";

        $debugUrl = "https://graph.facebook.com/debug_token";
        $params = [
            "input_token" => $tokenToCheck,
            "access_token" => "{$this->APP_ID}|{$this->APP_SECRET}"
        ];

        $resp = $this->httpGet($debugUrl, $params);
        $data = $resp["data"] ?? [];
        $isValid = $data["is_valid"] ?? false;
        $expiresAt = $data["expires_at"] ?? 0;

        if (!$isValid || time() > $expiresAt) {
            echo "[BİLGİ] Token süresi dolmuş, yenileniyor...\n";

            $oauthUrl = "https://www.facebook.com/{$this->API_VERSION}/dialog/oauth?" .
                "client_id={$this->APP_ID}" .
                "&redirect_uri=https://developers.facebook.com/tools/explorer/callback" .
                "&scope=pages_show_list,instagram_basic,instagram_content_publish,pages_read_engagement" .
                "&response_type=code";

            echo "🌐 URL’yi aç: $oauthUrl\n";
            echo "🔹 '?code=' parametresindeki kodu gir:\n";
            $code = trim(readline("CODE: "));

            if (!$code) {
                echo "[HATA] Code girilmedi.\n";
                return;
            }

            $tokenUrl = "https://graph.facebook.com/{$this->API_VERSION}/oauth/access_token";
            $params = [
                "client_id" => $this->APP_ID,
                "client_secret" => $this->APP_SECRET,
                "redirect_uri" => "https://developers.facebook.com/tools/explorer/callback",
                "code" => $code
            ];

            $resp = $this->httpGet($tokenUrl, $params);
            $newToken = $resp["access_token"] ?? "";

            if ($newToken) {
                $this->ACCESS_TOKEN = $newToken;
                echo "[OK] Yeni access_token alındı.\n";
            } else {
                echo "[HATA] Code -> Token dönüşüm hatası.\n";
                return;
            }
        } else {
            echo "[OK] Token geçerli, yenileme gerekmedi.\n";
        }

        // IG_USER_ID alma
        if (empty($this->IG_USER_ID)) {
            echo "[BİLGİ] IG_USER_ID çekiliyor...\n";
            $pagesUrl = "{$this->GRAPH_BASE}/me/accounts";
            $params = ["access_token" => $this->ACCESS_TOKEN];
            $pages = $this->httpGet($pagesUrl, $params);

            if (!empty($pages["data"][0]["id"])) {
                $this->IG_USER_ID = $pages["data"][0]["id"];
                echo "[OK] IG_USER_ID: {$this->IG_USER_ID}\n";
            } else {
                $meUrl = "{$this->GRAPH_BASE}/me";
                $params["fields"] = "id";
                $me = $this->httpGet($meUrl, $params);
                $this->IG_USER_ID = $me["id"] ?? "";
                echo "[OK] IG_USER_ID (user id): {$this->IG_USER_ID}\n";
            }
        }
    }

    // ==========================
    //  Temel HTTP Fonksiyonları
    // ==========================
    private function httpGet($url, $params = [])
    {
        $query = http_build_query($params);
        $ch = curl_init("{$url}?{$query}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    private function httpPost($url, $data = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    // ==========================
    //  API Yardımcıları
    // ==========================
    private function createContainer($type, $url, $caption)
    {
        $apiUrl = "{$this->GRAPH_BASE}/{$this->IG_USER_ID}/media";
        $data = ["access_token" => $this->ACCESS_TOKEN];

        if (!empty($caption)) {
            $data["caption"] = $caption;
        }
        if ($type === "image") {
            $data["image_url"] = $url;
        } else {
            $data["media_type"] = "VIDEO";
            $data["video_url"] = $url;
        }

        $j = $this->httpPost($apiUrl, $data);
        return $j["id"] ?? null;
    }

    private function publishContainer($creationId)
    {
        $apiUrl = "{$this->GRAPH_BASE}/{$this->IG_USER_ID}/media_publish";
        $data = ["creation_id" => $creationId, "access_token" => $this->ACCESS_TOKEN];
        $j = $this->httpPost($apiUrl, $data);
        return $j["id"] ?? null;
    }

    private function getStatus($creationId)
    {
        $url = "{$this->GRAPH_BASE}/{$creationId}";
        $params = ["fields" => "status_code,status", "access_token" => $this->ACCESS_TOKEN];
        return $this->httpGet($url, $params);
    }

    // ==========================
    //  Tekli Yükleme
    // ==========================
    public function uploadSingle($type, $url, $caption = "")
    {
        echo "→ Container oluşturuluyor...\n";
        $creationId = $this->createContainer($type, $url, $caption);

        if ($type === "video") {
            echo "→ Video işleniyor...\n";
            $waited = 0;
            while (true) {
                $st = $this->getStatus($creationId);
                $code = $st["status_code"] ?? "";
                echo "   - status_code={$code} (geçen={$waited}s)\n";
                if ($code === "FINISHED") break;
                if ($code === "ERROR") throw new Exception("Video işleme hatası");
                sleep($this->POLL_INTERVAL);
                $waited += $this->POLL_INTERVAL;
                if ($waited >= $this->TIMEOUT) throw new Exception("Zaman aşımı");
            }
        }

        echo "→ Yayınlanıyor...\n";
        $mediaId = $this->publishContainer($creationId);
        echo "✓ Yükleme tamamlandı. media_id={$mediaId}\n";
        return $mediaId;
    }

    // ==========================
    //  CSV'den Toplu Yükleme
    // ==========================
    public function uploadFromCSV($csvPath)
    {
        if (!file_exists($csvPath)) {
            throw new Exception("CSV dosyası bulunamadı.");
        }

        $rows = array_map("str_getcsv", file($csvPath));
        $headers = array_map("strtolower", $rows[0]);
        $typeIdx = array_search("type", $headers);
        $urlIdx  = array_search("url", $headers);
        $capIdx  = array_search("caption", $headers);

        if ($typeIdx === false || $urlIdx === false) {
            throw new Exception("CSV başlıkları 'type,url,caption' olmalı.");
        }

        $total = count($rows) - 1;
        $ok = 0; $fail = 0;

        for ($i = 1; $i <= $total; $i++) {
            $r = $rows[$i];
            $type = strtolower(trim($r[$typeIdx] ?? ""));
            $url = trim($r[$urlIdx] ?? "");
            $cap = trim($r[$capIdx] ?? "");

            if (!$type || !$url) continue;
            echo "[{$i}/{$total}] type={$type} url={$url}\n";

            try {
                $this->uploadSingle($type, $url, $cap);
                $ok++;
            } catch (Exception $e) {
                $fail++;
                echo "✗ Hata: " . $e->getMessage() . "\n";
            }
        }

        echo "[TOPLU] Tamamlandı. Başarılı={$ok}, Hatalı={$fail}, Toplam={$total}\n";
    }
}
?>
