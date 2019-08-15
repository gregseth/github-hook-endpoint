PHP Endpoint for GitHub Webhook URLs
====================================

If you have a server running PHP and want to use it as Github webhook endpoint, this script might be exactly what you need.

This script is a fork of [Gregor Aisch's gist](https://gist.github.com/gka/4627519), with a few fixes (no more IP based filter, …) and enhancements (using names for endpoints, checking the hash, …)

## Usage

1. Put `hooks.php` somewhere on your PHP-enabled web server, and make it accessible for the outside world. Let's say for now the script lives on http://example.com/hooks.php

2. Put [config.json](config.json) next to [hooks.php](hooks.php) and update it according to your needs.

    If you want email notification (yes, you want!), enter your email address to           `email.to`. To help yourself recognizing where these strange commit emails are comming from, you should set `email.from` to something meaningful like github-push-notification@example.com.

    You can use it for several repositories or branches at the same time by adding more entries to the `endpoints` dict. For each endpoint you need to set an *"endpoint_name"* for the dict entry and set `endpoint.repo` to *"username/reponame"*. You can configure endpoints for different branches, set `enpoint.branch` to the one you want.

    Set `endpoint.run` to the command you want to executde, it can be a simple command , or the path (relative or absolute) to an executable, e.g. `/path/to/update/script.sh`. Keep in mind the command is run as the account running your webserver, adjust your permissions accordingly.

    You can configure the `endpoint.secret` which is the key GitHub uses to authenticate the content of the webhook request. If you leave the value blank no check on the hash is performend when the hook is executed.

    For clarity, describe what happened after the update script has been executed under `endpoint.action`. Usually that's something like *"Your website XY has been updated."*. It will be used as subject in notification emails. This is especially helpful if you have multiple endpoints. The email will also contain the output of your update script and all the messages of the pushed commits.

3. If you don't want everybody to see your [config.json](config.json) (and you don't), either prevent access using [.htaccess](.htaccess) or the like, or move it to a secure location on your server. If you move it, make sure the PHP script knows where to find it.

4. Optional: you can use a rewrite rule (such as the one in the [.htaccess](.htaccess) file) to make the endpoint URL more user friendly.

5. On the settings page of your GitHub repository, go to **Service Hooks** > **WebHook URLs** and enter the public url of your `hooks.php` (e.g. http://example.com/hooks.php?repo=endpoint_name, or if you used the rewrite rule http://example.com/hook/endpoint_name). 

That's it, you're all set.