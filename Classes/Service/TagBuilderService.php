<?php

namespace BeechIt\Bynder\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Service: TagBuilder
 * @package BeechIt\Bynder\Service
 */
class TagBuilderService
{

    /**
     * @param string $name
     * @param string $content
     * @return TagBuilder
     */
    public function getTagBuilder($name = '', $content = '')
    {
        return GeneralUtility::makeInstance(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class, $name, $content);
    }

    /**
     * @param TagBuilder $tagBuilder
     * @param $arguments
     * @return TagBuilder
     */
    public function initializeAbstractTagBasedAttributes(TagBuilder $tagBuilder, $arguments): TagBuilder
    {
        if ($arguments['additionalAttributes'] && is_array($arguments['additionalAttributes'])) {
            $tagBuilder->addAttributes($arguments['additionalAttributes']);
        }

        if ($arguments['data'] && is_array($arguments['data'])) {
            foreach ($arguments['data'] as $dataAttributeKey => $dataAttributeValue) {
                $tagBuilder->addAttribute('data-' . $dataAttributeKey, $dataAttributeValue);
            }
        }
        $this->initializeUniversalTagAttributes($tagBuilder, $arguments);
        return $tagBuilder;
    }

    /**
     * @param TagBuilder $tagBuilder
     * @param array $arguments
     * @param array $universalTagAttributes
     * @return TagBuilder
     */
    public function initializeUniversalTagAttributes(
        TagBuilder $tagBuilder,
        array $arguments,
        array $universalTagAttributes = ['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick']
    ): TagBuilder {
        foreach ($universalTagAttributes as $attributeName) {
            if ($arguments[$attributeName] && $arguments[$attributeName] !== '') {
                $tagBuilder->addAttribute($attributeName, $arguments[$attributeName]);
            }
        }
        return $tagBuilder;
    }
}
