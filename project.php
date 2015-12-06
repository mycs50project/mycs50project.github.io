<?php

// ensure proper usage
    if (empty($_GET["q"]))
    {
        http_response_code(400);
        exit;
    }

    // escape user's input
    $search = urlencode($_GET["q"]);

    // numerically indexed array of articles
    $articles = [];
    
    // headers for proxy servers
    $headers = [
        "Accept" => "*/*",
        "Connection" => "Keep-Alive",
        "User-Agent" => sprintf("curl/%s", curl_version()["version"])
    ];

    // download recipes from BBC
    $context = stream_context_create([
        "http" => [
            "header" => implode(array_map(function($value, $key) { return sprintf("%s: %s\r\n", $key, $value); }, $headers, array_keys($headers))),
            "method" => "GET"
        ]
    ]);
    $contents = @file_get_contents("http://www.bbcgoodfood.com/search/recipes?query={$search}, false, $context);
    if ($contents === false)
    {
        http_response_code(503);
        exit;
    }

    // parse RSS
    $rss = @simplexml_load_string($contents);
    @fclose($handle);
    if ($rss === false)
    {
        http_response_code(500);
        exit;
    }

    // iterate over items in channel
    foreach ($rss->channel->item as $item)
    {
        // add article to array
        $articles[] = [
            "link" => (string) $item->link,
            "title" => (string) $item->title
        ];
    }

    // output articles as JSON (pretty-printed for debugging convenience)
    header("Content-type: application/json");
    print(json_encode($articles, JSON_PRETTY_PRINT));

?>

