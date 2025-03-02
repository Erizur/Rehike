<?php
namespace Rehike\Player;

use Rehike\Player\Exception\UpdaterException;

/**
 * Retrieve information about the current player.
 * 
 * Two important variables must be retrieved in order to properly
 * use the YouTube player, no matter the circumstances. These are
 * the URL of the JS program itself and the "signature timestamp",
 * which is used to prevent custom applications like this. oops!
 * 
 * This process involves two separate web requests, so it's useful
 * to cache the result for some reasonable amount of time instead
 * of requerying each subsequent visit.
 * 
 * @author Daylin Cooper <dcoop2004@gmail.com>
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 */
class PlayerUpdater
{
    /**
     * The URL for the YouTube player JS can only be found
     * on certain pages, such as watch or embed pages. However,
     * the JS binary remains the same between both.
     * 
     * As such, in order to get the JS URL, we need to query
     * some such YouTube URL.
     * 
     * Any embed URL works perfectly fine, no matter how invalid.
     * In fact, https://www.youtube.com/embed with no additional
     * parameters works perfectly fine and returns the JS URL,
     * but I want the requests to look legitimate.
     * 
     * So the URL provided by default is that of "Me at the zoo", a
     * very important video in YouTube's history, one that receives
     * plenty of views, and one that's very likely never going anywhere.
     * 
     * TEMP: Force player to be en-US. Should be removed with i18n v2.
     */
    protected static string $sourceUrl = "https://www.youtube.com/embed/jNQXAC9IVRw?hl=en&gl=US";

    /**
     * Request all necessary player information for the latest
     * version.
     */
    public static function requestPlayerInfo(): object
    {
        $html = self::requestAppHtml();

        // Attempt to get application URLs from the
        // response.
        $jsUrl = self::extractApplicationUrl($html);
        $cssUrl = self::extractApplicationCss($html);
        $embedJsUrl = self::extractEmbedUrl($html);

        // Now get the sts from the application itself.
        $js = self::requestApplication(self::unrelativize($jsUrl));

        $sts = self::extractSts($js);

        // Pack these up and return:
        return (object)[
            "baseJsUrl" => $jsUrl,
            "baseCssUrl" => $cssUrl,
            "embedJsUrl" => $embedJsUrl,
            "signatureTimestamp" => $sts
        ];
    }
    
    /**
     * Get the YouTube Player JS application URL.
     * 
     * These vary with VFL hash (base position) and localisation,
     * otherwise they are very uniform URLs.
     * 
     * Since YouTube changed their static URL system circa 2020, these
     * assets are not stored permanently (i.e. these might expire mid-use
     * if the cache duration is too long).
     */
    public static function requestAppHtml(): string
    {
        $response = Network::request(self::$sourceUrl);

        return $response;
    }

    /**
     * Download the player application so that the
     * signature timestamp can be extracted from it.
     */
    public static function requestApplication(string $playerUrl): string
    {
        return Network::request($playerUrl);
    }

    /**
     * Extract the player application URL from a HTML response.
     */
    public static function extractApplicationUrl(string $html): string
    {
        $status = preg_match(PlayerCore::$playerJsRegex, $html, $matches);

        if (false != $status)
        {
            return $matches[0];
        }
        else
        {
            throw new UpdaterException("Failed to extract application URL");
        }
    }

    /**
     * Extract the URL of the player CSS from a HTML response.
     */
    public static function extractApplicationCss(string $html): string
    {
        $status = preg_match(PlayerCore::$playerCssRegex, $html, $matches);

        if (false != $status)
        {
            return $matches[0];
        }
        else
        {
            throw new UpdaterException("Failed to get application CSS endpoint");
        }
    }

    /**
     * Extract the player embed JS URL from a HTML response.
     */
    public static function extractEmbedUrl(string $html): string
    {
        $status = preg_match(PlayerCore::$embedJsRegex, $html, $matches);

        if (false != $status)
        {
            return $matches[0];
        }
        else
        {
            throw new UpdaterException("Failed to extract application URL");
        }
    }

    /**
     * Extract the signature timestamp of a player application.
     * 
     * Signature timestamp, or STS, is a security key that YouTube
     * includes to prevent unauthorised access to stream URLs. This
     * is, funnily enough, useless if you have the player request
     * the video itself, but for many other cases it is required.
     * 
     * Without the signature timestamp, the streams will fail to 
     * download or be severely throttled to the point where watching
     * the video is impossible.
     * 
     * STS is synchronised between the player source code and the
     * InnerTube player API, making it necessary to also forward this
     * value to that if you're intending on using the API directly.
     */
    public static function extractSts(string $player): string
    {
        // Pretty lazy code here, but it works
        preg_match(PlayerCore::$stsRegex, $player, $matches);

        if (isset($matches[1]))
        {
            return (int)$matches[1];
        }
        else
        {
            throw new UpdaterException(
                "Failed to get signature timestamp of player"
            );
        }
    }

    /**
     * Make a relative URL (path) not relative.
     * 
     * @param string $url
     * @param string $base to prepend
     */
    public static function unrelativize(
            string $path, 
            string $base = "https://www.youtube.com"
    ): string
    {
        if ("/" == $path[0])
        {
            return $base . $path;
        }
        else
        {
            return $path;
        }
    }
}