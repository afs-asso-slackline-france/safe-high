<?php
//https://sandbox-live.safesky.app/
class SafeSkyClient
{
    private string $apiKey;
    private string $host = "uav-api.safesky.app";

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }


    /* =========================
       🟢 ADVISORY (ZONE GEOJSON)
    ========================= */
    public function sendAdvisory(array $featureCollection): array
    {
        return $this->request("/v1/advisory", $featureCollection);
    }

    /* =========================
       CORE REQUEST (HMAC SAFE SKY)
    ========================= */
    private function request(string $path, array $payload): array
    {
        $method = "POST";
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $nonce = $this->uuidv4();

        $kid = $this->deriveKid($this->apiKey);
        $hmacKey = $this->deriveHmacKey($this->apiKey);

        $canonical = $this->buildCanonicalRequest(
            $method,
            $path,
            "",
            $this->host,
            $timestamp,
            $nonce,
            $body
        );

        $signature = $this->sign($canonical, $hmacKey);

        $headers = [
            "Content-Type: application/json",
            "Host: {$this->host}",
            "X-SS-Date: {$timestamp}",
            "X-SS-Nonce: {$nonce}",
            "X-SS-Alg: SS-HMAC-SHA256-V1",
            "Authorization: SS-HMAC Credential={$kid}/v1, SignedHeaders=host;x-ss-date;x-ss-nonce, Signature={$signature}"
        ];

        $url = "https://{$this->host}{$path}";

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            "http" => $httpCode,
            "response" => $response
        ];
    }

    /* =========================
       KID + HMAC KEY
    ========================= */
    private function deriveKid(string $apiKey): string
    {
        $hash = hash('sha256', "kid:" . $apiKey, true);
        $kid = substr($hash, 0, 16);
        return rtrim(strtr(base64_encode($kid), '+/', '-_'), '=');
    }

    private function deriveHmacKey(string $apiKey): string
    {
        return hash_hkdf(
            "sha256",
            $apiKey,
            32,
            "auth-v1",
            "safesky-hmac-salt-v1"
        );
    }

    /* =========================
       CANONICAL REQUEST
    ========================= */
    private function buildCanonicalRequest(
        string $method,
        string $path,
        string $query,
        string $host,
        string $date,
        string $nonce,
        string $body
    ): string {
        $bodyHash = hash('sha256', $body ?? "");

        return implode("\n", [
            strtoupper($method),
            $path,
            $query,
            "host:" . $host,
            "x-ss-date:" . $date,
            "x-ss-nonce:" . $nonce,
            "",
            $bodyHash
        ]);
    }

    /* =========================
       SIGNATURE
    ========================= */
    private function sign(string $canonical, string $key): string
    {
        return base64_encode(
            hash_hmac('sha256', $canonical, $key, true)
        );
    }

    /* =========================
       UUID v4
    ========================= */
    private function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
