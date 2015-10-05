#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

$secret = json_decode(file_get_contents('./secret.json'), true);
$pageId = $secret['page_id'];

$fb = new Facebook\Facebook([
    'app_id' => $secret['app_id'],
    'app_secret' => $secret['app_secret'],
    'default_graph_version' => 'v2.4',
    'default_access_token' => $secret['default_access_token']
]);

$nextPost = function () use ($fb, $pageId) {
    static $response, $feedEdge, $counter = 0;
    if ($response === null) {
        $response = $fb->sendRequest('GET', "/$pageId/posts?fields=id,message,created_time,full_picture");
        $feedEdge = $response->getGraphEdge();
    }

    if ($counter >= count($feedEdge)) {
        $feedEdge = $fb->next($feedEdge);
        $counter = 0;
    }

    if (count($feedEdge) === 0) {
        return null;
    }

    return $feedEdge[$counter++]->asArray();
};

$isSegredo = function ($post) {
    return isset($post['message']) && preg_match('/^(#\s*\d+|#\s+|"\d+\s+|confesso que)/i', $post['message']);
};

$segredos = [];

for ($post = $nextPost(); $post !== null; $post = $nextPost()) {
    if (!$isSegredo($post)) {
        /*if (isset($post['message']) && !isset($post['story'])) {
            var_dump($post);
        }*/
    } else {
        echo $post['message'] . "\n";
 
        if (isset($post['full_picture'])) {
            echo $post['full_picture'] . "\n";
        }
        echo "\n";

        $postId = substr($post['id'], strlen("{$pageId}_"));
        $permalink = "https://www.facebook.com/permalink.php?story_fbid={$postId}&id={$pageId}";
        echo $permalink . "\n";

        echo str_repeat('-', 80) . "\n";
    }
}
