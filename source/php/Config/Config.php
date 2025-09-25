<?php

namespace adApiWpIntegration\Config;

use WpService\Contracts\ApplyFilters;
use adApiWpIntegration\Config\ConfigInterface;

/**
 * Active Directory Integration configuration.
 * 
 * This class follows the Single Responsibility Principle by handling only
 * configuration management. It implements the Dependency Inversion Principle
 * by depending on the ApplyFilters abstraction rather than WordPress directly.
 */
class Config implements ConfigInterface
{
    public function __construct(
        private ApplyFilters $wpService,
        private string $filterPrefix = 'ActiveDirectoryApi/Config'
    ) {
    }

    /**
     * Get the Active Directory integration URL.
     */
    public function getAdIntegrationUrl(): ?string
    {
        $url = defined('AD_INTEGRATION_URL') ? constant('AD_INTEGRATION_URL') : null;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $url
        );
    }

    /**
     * Check if name updates are enabled.
     */
    public function isUpdateNameEnabled(): bool
    {
        $enabled = defined('AD_UPDATE_NAME') ? constant('AD_UPDATE_NAME') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if email updates are enabled.
     */
    public function isUpdateEmailEnabled(): bool
    {
        $enabled = defined('AD_UPDATE_EMAIL') ? constant('AD_UPDATE_EMAIL') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if meta updates are enabled.
     */
    public function isUpdateMetaEnabled(): bool
    {
        $enabled = defined('AD_UPDATE_META') ? constant('AD_UPDATE_META') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Get the meta prefix for AD fields.
     */
    public function getMetaPrefix(): string
    {
        $prefix = defined('AD_META_PREFIX') ? constant('AD_META_PREFIX') : 'ad_';
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $prefix
        );
    }

    /**
     * Check if passwords should be saved.
     */
    public function isSavePasswordEnabled(): bool
    {
        $enabled = defined('AD_SAVE_PASSWORD') ? constant('AD_SAVE_PASSWORD') : false;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if random passwords should be generated.
     */
    public function isRandomPasswordEnabled(): bool
    {
        $enabled = defined('AD_RANDOM_PASSWORD') ? constant('AD_RANDOM_PASSWORD') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if bulk import is enabled.
     */
    public function isBulkImportEnabled(): bool
    {
        $enabled = defined('AD_BULK_IMPORT') ? constant('AD_BULK_IMPORT') : false;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Get the default role for bulk import.
     */
    public function getBulkImportRole(): string
    {
        $role = defined('AD_BULK_IMPORT_ROLE') ? constant('AD_BULK_IMPORT_ROLE') : 'subscriber';
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $role
        );
    }

    /**
     * Check if bulk import propagation is enabled.
     */
    public function isBulkImportPropagateEnabled(): bool
    {
        $enabled = defined('AD_BULK_IMPORT_PROPAGATE') ? constant('AD_BULK_IMPORT_PROPAGATE') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if cleaning actions are enabled.
     */
    public function isCleaningEnabled(): bool
    {
        $enabled = defined('AD_CLEANING') ? constant('AD_CLEANING') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if user auto-creation is enabled.
     */
    public function isAutoCreateUserEnabled(): bool
    {
        $enabled = defined('AD_AUTOCREATE_USER') ? constant('AD_AUTOCREATE_USER') : false;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Get the default role for auto-created users.
     */
    public function getAutoCreateRole(): string
    {
        $role = defined('AD_AUTOCREATE_ROLE') ? constant('AD_AUTOCREATE_ROLE') : 'subscriber';
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $role
        );
    }

    /**
     * Get the user domain for AD users.
     */
    public function getUserDomain(): ?string
    {
        $domain = defined('AD_USER_DOMAIN') ? constant('AD_USER_DOMAIN') : null;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $domain
        );
    }

    /**
     * Get bulk import credentials.
     */
    public function getBulkImportUser(): ?string
    {
        $user = defined('AD_BULK_IMPORT_USER') ? constant('AD_BULK_IMPORT_USER') : null;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $user
        );
    }

    public function getBulkImportPassword(): ?string
    {
        $password = defined('AD_BULK_IMPORT_PASSWORD') ? constant('AD_BULK_IMPORT_PASSWORD') : null;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $password
        );
    }

    /**
     * Get reassign username for deleted users.
     */
    public function getBulkImportReassignUsername(): ?string
    {
        $username = defined('AD_BULK_IMPORT_REASSIGN_USERNAME') ? constant('AD_BULK_IMPORT_REASSIGN_USERNAME') : null;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $username
        );
    }

    /**
     * Check if honeypot validation is enabled.
     */
    public function isHoneyPotValidationEnabled(): bool
    {
        $enabled = defined('AD_HP_VALIDATION') ? constant('AD_HP_VALIDATION') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Check if nonce validation is enabled.
     */
    public function isNonceValidationEnabled(): bool
    {
        $enabled = defined('AD_NONCE_VALIDATION') ? constant('AD_NONCE_VALIDATION') : true;
        
        return $this->wpService->applyFilters(
            $this->createFilterKey(__FUNCTION__),
            $enabled
        );
    }

    /**
     * Create a filter key with the configured prefix.
     */
    private function createFilterKey(string $filter = ""): string
    {
        return $this->filterPrefix . "/" . ucfirst($filter);
    }
}