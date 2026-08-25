<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Compatibility;

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/**
 * Registers Extbase plugins as dedicated CType content elements.
 *
 * TYPO3 12/13 expect the 5th argument PLUGIN_TYPE_CONTENT_ELEMENT.
 * TYPO3 14 removed the parameter (all plugins are CTypes) but still
 * accepts the value "CType" when it is passed.
 */
final class PluginRegistration
{
    /**
     * @param array<class-string, string> $controllerActions
     * @param array<class-string, string> $nonCacheableControllerActions
     */
    public static function configure(
        string $extensionName,
        string $pluginName,
        array $controllerActions,
        array $nonCacheableControllerActions = [],
    ): void {
        $arguments = [
            $extensionName,
            $pluginName,
            $controllerActions,
            $nonCacheableControllerActions,
        ];

        $pluginType = self::contentElementPluginType();
        if ($pluginType !== null) {
            $arguments[] = $pluginType;
        }

        ExtensionUtility::configurePlugin(...$arguments);
    }

    private static function contentElementPluginType(): ?string
    {
        if (defined(ExtensionUtility::class . '::PLUGIN_TYPE_CONTENT_ELEMENT')) {
            return ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT;
        }

        $reflection = new \ReflectionMethod(ExtensionUtility::class, 'configurePlugin');
        if ($reflection->getNumberOfParameters() >= 5) {
            return 'CType';
        }

        return null;
    }
}
