<?php namespace Watson\Taggly;

class Taggly {

    /**
     * The tags for the cloud.
     *
     * @var array
     */
    protected $tags;

    /**
     * The minimum tag count.
     *
     * @var int
     */
    protected $minimumCount;

    /**
     * The maximum tag count.
     *
     * @var int
     */
    protected $maximumCount;

    /**
     * The minimum font size.
     *
     * @var int
     */
    protected $minimumFontSize = 12;

    /**
     * The maximum font size.
     *
     * @var int
     */
    protected $maximumFontSize = 24;

    /**
     * Whether to shuffle the tags.
     *
     * @var bool
     */
    protected $shuffleTags = true;

    public $autoRefresh = False;

    protected $_countMin;
    protected $_countMax;
    protected $_countRange;
    protected $_countFactor;
    protected $_fontSizeRange;
    protected $_fontSizeFactor;

    /**
     * Get the tags.
     *
     * @return array
     */
    public function getTags()
    {
        return is_array($this->tags) ? $this->tags : [];
    }

    public function setTags(array $tags = [])
    {
        $this->tags = [];

        foreach ($tags as $tag)
        {
            $this->tags[] = $tag instanceof Tag ? $tag : new Tag($tag);
        }
    }

    /**
     * Get the lowest count of the tags.
     *
     * @return int
     */
    public function getMinimumCount($refresh = False)
    {

        // Only calculate the value if we need/want to
        if (!$refresh && !$this->autoRefresh && isset($this->_countMin)) return $this->_countMin;

        $minimumCount = 0;

        foreach ($this->getTags() as $tag)
        {
            if ( ! $minimumCount)
            {
                $minimumCount = $tag->getCount();
            } 
            else if ($minimumCount > $tag->getCount()) 
            {
                $minimumCount = $tag->getCount();
            }
        }

        $this->_countMin = $minimumCount;
        return $this->_countMin;
    }

    /**
     * Get the highest count of the tags.
     *
     * @return int
     */
    public function getMaximumCount($refresh = False)
    {
        // Only calculate the value if we need/want to
        if (!$refresh && !$this->autoRefresh && isset($this->_countMax)) return $this->_countMax;

        $maximumCount = null;

        foreach ($this->getTags() as $tag)
        {
            if ( ! $maximumCount)
            {
                $maximumCount = $tag->getCount();
            }
            else if ($maximumCount < $tag->getCount())
            {
                $maximumCount = $tag->getCount();
            }
        }

        $this->_countMax = $maximumCount;
        return $this->_countMax;
    }

    /**
     * Get the range between the highest and lowest tag count.
     *
     * @return int
     */
    public function getCountRange($refresh = False)
    {
        // Only calculate the value if we need/want to
        if (!$refresh && !$this->autoRefresh && isset($this->_countRange)) return $this->_countRange;

        $range = $this->getMaximumCount($refresh) - $this->getMinimumCount($refresh);

        $this->_countRange = ($range < 1) ? 1 : $range;
        return $this->_countRange;
    }

    public function getCountFactor($refresh = False)
    {

        if (!$refresh && !$this->autoRefresh && isset($this->_countRange)) return $this->_countFactor;

        $this->_countFactor = $this->getCountRange() / 100;

        return $this->_countFactor;

    }

    public function getFontSizeRange($refresh = False)
    {
        if (!$refresh && !$this->autoRefresh && isset($this->_fontSizeRange)) return $this->_fontSizeRange;

        $range = $this->getMaximumFontSize() - $this->getMinimumFontSize();

        $this->_fontSizeRange = ($range < 1) ? 1 : $range;

        return $this->_fontSizeRange;

    }

    public function getFontSizeFactor($refresh = False)
    {

        if (!$refresh && !$this->autoRefresh && isset($this->_fontSizeFactor)) return $this->_fontSizeFactor;

        $this->_fontSizeFactor = $this->getFontSizeRange() / 100;

        return $this->_fontSizeFactor;

    }

    public function getCountPercentile(Int $count)
    {
        return ($count - $this->getMinimumCount()) / $this->getCountFactor();

    }

    /**
     * Get the minimum font size.
     *
     * @return int
     */
    public function getMinimumFontSize()
    {
        return $this->minimumFontSize;
    }

    /**
     * Set the minimum font size.
     *
     * @param  int  $value
     * @return void
     */
    public function setMinimumFontSize($value)
    {
        $this->minimumFontSize = (int) $value;
    }

    /**
     * Get the maximum font size.
     *
     * @return int
     */
    public function getMaximumFontSize()
    {
        return $this->maximumFontSize;
    }

    /**
     * Set the maximum font size.
     *
     * @param  int  $value
     * @return void
     */
    public function setMaximumFontSize($value)
    {
        $this->maximumFontSize = (int) $value;
    }

    /**
     * Get whether the tags are being shuffled.
     *
     * @return bool
     */
    public function getShuffleTags()
    {
        return $this->shuffleTags;
    }

    /**
     * Set whether the tags are being shuffled.
     *
     * @param  bool  $value
     * @return void
     */
    public function setShuffleTags($value)
    {
        $this->shuffleTags = (bool) $value;
    }

    /**
     * Generate a tag cloud using either the tags provided or tags
     * that have already been registered.
     *
     * @param  array  $tags
     * @return string
     */
    public function cloud(Array $tags = null)
    {
        if ($tags) $this->setTags($tags);

        $tags = $this->getTags() ?: [];

        $output = '';

        if ($this->getShuffleTags()) shuffle($tags);

        foreach ($tags as $tag)
        {
            $output .= $this->getTagElement($tag);
        }

        return '<div class="tags">'.$output.'</div>';
    }

    /**
     * Get the font size in units for a given tag.
     *
     * @param  Tag  $tag
     * @return int
     */
    public function getFontSize(Tag $tag)
    {


    $countPercentile = $this->getCountPercentile($tag->getCount());
    $fontSize = $this->getMinimumFontSize() + ($countPercentile * $this->getFontSizeFactor());

    $data = [
        'minFontSize' => $this->getMinimumFontSize(),
        'maxFontSize' => $this->getMaximumFontSize(),
        'fontRange'   => $this->getFontSizeRange(),
        'fontFactor'  => $this->getFontSizeFactor(),
        '----------'  => '-------------------------',
        'minCount'    => $this->getMinimumCount(),
        'maxCount'    => $this->getMaximumCount(),
        'countRange'  => $this->getCountRange(),
        'countFactor' => $this->getCountFactor(),
        'countPtile'  => $countPercentile,
        '----------'  => '-------------------------',
        'formula'     => $this->getMinimumFontSize().' + ('.$countPercentile.' * '.$this->getFontSizeFactor().' )',
        'fontSize'    => $fontSize,


    ];

    error_log(print_r($data, True));

    return $fontSize;

        return $this->getMinimumFontSize() + ($tag->getCount() - $this->getMinimumCount()) 
            + ($this->getMaximumFontSize() - $this->getMinimumFontSize()) / $this->getCountRange();
    }

    /**
     * Get the element for a given tag.
     *
     * @param  Tag  $tag
     * @return string
     */
    public function getTagElement(Tag $tag)
    {
        $fontSize = $this->getFontSize($tag);

        if ($tag->getUrl())
        {
            return '<a href="'.$tag->getUrl().'" class="tag" title="'.$tag->getTag().'" '
                .'style="font-size: '.floor($fontSize).'px">'.e($tag->getTag()).'</a>';
        }

        return '<span class="tag" title="'.$tag->getTag().'" style="font-size: '
            .floor($fontSize).'px">'.e($tag->getTag()).'</span>';
    }


}
