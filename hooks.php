<?php

/*
 * Endpoint for Github Webhook URLs
 *
 * see: https://help.github.com/articles/post-receive-hooks
 *
 */

$debug = TRUE;

function run($payload, $endpoint) {
    global $config;

    if ($debug) echo "hook handling started...\n";
    // check if the push came from the right repository and branch
    if ($payload->repository->url != 'https://api.github.com/repos/' . $endpoint->repo)
        throw new Exception("The source repository $payload->repository->url doesn't match the expected one $endpoint->repo");
    if ($debug) echo "endpoint found...\n";
    // execute update script, and record its output
    ob_start();
    passthru($endpoint->run, $rc);
    if ($debug) echo "hook executed ($rc)...\n";
    $output = ob_get_contents();
    if ($debug) echo "hook executed:\n".$output;
    ob_end_flush();
    // prepare and send the notification email
    if (isset($config->email)) {
        // send mail to someone, and the github user who pushed the commit
        $headers = 'From: '.$config->email->from."\r\n";
        $headers .= 'CC: ' . $payload->pusher->email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $body = '<p>The Github user <a href="https://github.com/'
        . $payload->pusher->name .'">@' . $payload->pusher->name . '</a>'
        . ' has pushed to ' . $payload->repository->url
        . ' and consequently, ' . $endpoint->action
        . '.</p>';

        $body .= '<p>Here\'s a brief list of what has been changed:</p>';
        $body .= '<ul>';
        foreach ($payload->commits as $commit) {
            $body .= '<li>'.$commit->message.'<br />';
            $body .= '<small style="color:#999">added: <b>'.count($commit->added)
                .'</b> &nbsp; modified: <b>'.count($commit->modified)
                .'</b> &nbsp; removed: <b>'.count($commit->removed)
                .'</b> &nbsp; <a href="' . $commit->url
                . '">read more</a></small></li>';
        }
        $body .= '</ul>';
        $body .= '<p>What follows is the output of the script:</p><pre>';
        $body .= $output. '</pre>';
        $body .= '<p>Cheers, <br/>Github Webhook Endpoint</p>';

        mail($config->email->to, $endpoint->action, $body, $headers);
    }
    return true;
}

// read config.json
$config_filename = 'config.json';
if (!file_exists($config_filename)) {
    echo "Can't find ".$config_filename;
}
$config = json_decode(file_get_contents($config_filename));

try {
    $payload = file_get_contents('php://input');
    if (empty($payload)) {
        echo "This is fine.";
    } else {
        if (empty($_GET['repo']))
            throw new Exception("No repository specified, the hook URL must look like https://gregseth.net/git/hooks/<repo_name>");
        $repo = $_GET['repo'];

        if (empty($config->endpoints->$repo))
            throw new Exception("No configuration found for $repo.");
        $repo_config = $config->endpoints->$repo;

        if (!empty($repo_config->secret)) {
            $hash = 'sha1='.hash_hmac('sha1', $payload, $repo_config->secret);
            $header_hash = $_SERVER['HTTP_X_HUB_SIGNATURE'];
            if ($hash != $header_hash)
                throw new Exception("The recieved hash ($header_hash) doesn't match the computed one ($hash).");
        } // else, there's no secret configured, we assume it's ok
        run(json_decode($payload), $repo_config);
    }
} catch ( Exception $e ) {
    $msg = $e->getMessage();
    echo $msg;
    mail($config->email->to, $msg, ''.$e);
}

