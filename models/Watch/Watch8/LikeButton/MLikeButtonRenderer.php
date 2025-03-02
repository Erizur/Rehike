<?php
namespace Rehike\Model\Watch\Watch8\LikeButton;

use Rehike\Util\ExtractUtils;

/**
 * Defines the like button (and dislike button) container.
 * 
 * This stores two copies of both the like and dislike buttons, which are
 * used for their individual activation states. This is how hitchhiker
 * handled this.
 */
class MLikeButtonRenderer
{
    /** @var MLikeButton */
    public $likeButton;
    public $activeLikeButton;

    /** @var MDislikeButton */
    public $dislikeButton;
    public $activeDislikeButton;

    /** @var MSparkbars */
    public $sparkbars;

    public function __construct($dataHost, &$info, &$videoId)
    {
        if (isset($info->topLevelButtons[0] ->segmentedLikeDislikeButtonRenderer)) {
            $origLikeButton = $info->topLevelButtons[0]->segmentedLikeDislikeButtonRenderer->likeButton->toggleButtonRenderer ?? null;
            $origDislikeButton = $info->topLevelButtons[0]->segmentedLikeDislikeButtonRenderer->dislikeButton->toggleButtonRenderer ?? null;
        } else {
            $origLikeButton = $info->topLevelButtons[0]->toggleButtonRenderer ?? null;
            $origDislikeButton = $info->topLevelButtons[0]->toggleButtonRenderer ?? null;
        }

        $likeA11y = $origLikeButton->accessibility->label ?? "";
        $dislikeA11y = $origDislikeButton->accessibility->label ?? "";

        $isLiked = $origLikeButton->isToggled ?? false;
        $isDisliked = $origDislikeButton->isToggled ?? false;

        // Extract like count from like count string
        $likeCount = ExtractUtils::isolateLikeCnt($likeA11y ?? "");
        
        if (is_numeric(str_replace(",", "", $likeCount)))
            $likeCountInt = (int)str_replace(",", "", $likeCount);

        // Account for RYD API data if it exists
        $rydData = &$dataHost::$rydData;
        if ($dataHost::$useRyd && "" !== $likeCount && isset($rydData->dislikes))
        {
            $dislikeCountInt = (int)$rydData->dislikes;

            $this->sparkbars = new MSparkbars($likeCountInt, $dislikeCountInt);
        }

        $likeCountOff = @$likeCountInt;
        $likeCountOn = @$likeCountInt + 1;

        $dislikeCountOff = @$dislikeCountInt;
        $dislikeCountOn = @$dislikeCountInt + 1;

        $this->likeButton = new MLikeButton($likeCountOff, $likeA11y, !$isLiked, $videoId);
        $this->activeLikeButton = new MLikeButton($likeCountOn, $likeA11y, $isLiked, $videoId, true);
        $this->dislikeButton = new MDislikeButton($dislikeCountOff, $dislikeA11y, !$isDisliked, $videoId);
        $this->activeDislikeButton = new MDislikeButton($dislikeCountOn, $dislikeA11y, $isDisliked, $videoId, true);
    }
}