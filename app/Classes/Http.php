<?php


class Http
{
    static private function http_build_query_for_curl($arrays, &$new = array(), $prefix = null)
    {

        if (is_object($arrays)) {
            $arrays = get_object_vars($arrays);
        }

        foreach ($arrays as $key => $value) {
            $k = isset($prefix) ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value) or is_object($value)) {
                self::http_build_query_for_curl($value, $new, $k);
            } else {
                $new[$k] = $value;
            }
        }
    }

    static public function request($url, $params = [], $headers = [], $method='get', $timeout = 15, $manualProxy = "")
    {
        $method = strtolower($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($manualProxy != "") {
            if (is_array($manualProxy)) {
                curl_setopt($ch, CURLOPT_PROXY, $manualProxy['ip']);
                curl_setopt($ch, CURLOPT_PROXYPORT, $manualProxy['port']);
            } else {
                curl_setopt($ch, CURLOPT_PROXY, $manualProxy);
            }

        }

        $headers['Content-Type'] = 'application/json';

        if ($headers && sizeof($headers) > 0) {
            foreach ($headers as $k => $v) {
                if (is_numeric($k)) {
                    continue;
                }

                $headers[] = "{$k}: {$v}";
                unset($headers[$k]);
            }
            $headers = array_filter($headers);
            $headers = array_values($headers);
        }

        if ($method=='get' && $params) {
            self::http_build_query_for_curl($params, $params);
        }

//        curl_setopt($ch, CURLOPT_POST, count($params ?? []));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params ?? null);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        $json = json_decode($data, true);

        if ($json) {
            $data = $json;
        }

        $error = curl_error($ch);
//        $errorNo = curl_errno($ch);
//        $response_headers = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return $data;
    }

}