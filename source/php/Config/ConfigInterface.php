<?php

namespace adApiWpIntegration\Config;

/**
 * Interface for Active Directory Integration configuration.
 * 
 * This interface follows the Interface Segregation Principle by defining
 * only configuration-related methods needed by the system.
 */
interface ConfigInterface
{
    /**
     * Get the Active Directory integration URL.
     */
    public function getAdIntegrationUrl(): ?string;

    /**
     * Check if name updates are enabled.
     */
    public function isUpdateNameEnabled(): bool;

    /**
     * Check if email updates are enabled.
     */
    public function isUpdateEmailEnabled(): bool;

    /**
     * Check if meta updates are enabled.
     */
    public function isUpdateMetaEnabled(): bool;

    /**
     * Get the meta prefix for AD fields.
     */
    public function getMetaPrefix(): string;

    /**
     * Check if passwords should be saved.
     */
    public function isSavePasswordEnabled(): bool;

    /**
     * Check if random passwords should be generated.
     */
    public function isRandomPasswordEnabled(): bool;

    /**
     * Check if bulk import is enabled.
     */
    public function isBulkImportEnabled(): bool;

    /**
     * Get the default role for bulk import.
     */
    public function getBulkImportRole(): string;

    /**
     * Check if bulk import propagation is enabled.
     */
    public function isBulkImportPropagateEnabled(): bool;

    /**
     * Check if cleaning actions are enabled.
     */
    public function isCleaningEnabled(): bool;

    /**
     * Check if user auto-creation is enabled.
     */
    public function isAutoCreateUserEnabled(): bool;

    /**
     * Get the default role for auto-created users.
     */
    public function getAutoCreateRole(): string;

    /**
     * Get the user domain for AD users.
     */
    public function getUserDomain(): ?string;

    /**
     * Get bulk import credentials.
     */
    public function getBulkImportUser(): ?string;
    public function getBulkImportPassword(): ?string;

    /**
     * Get reassign username for deleted users.
     */
    public function getBulkImportReassignUsername(): ?string;

    /**
     * Check if honeypot validation is enabled.
     */
    public function isHoneyPotValidationEnabled(): bool;

    /**
     * Check if nonce validation is enabled.
     */
    public function isNonceValidationEnabled(): bool;
}