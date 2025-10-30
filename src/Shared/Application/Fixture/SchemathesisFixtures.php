<?php

declare(strict_types=1);

namespace App\Shared\Application\Fixture;

final class SchemathesisFixtures
{
    // Primary Customer - Used for general API testing
    public const CUSTOMER_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0Z1';
    public const CUSTOMER_EMAIL = 'customer@example.com';
    public const CUSTOMER_INITIALS = 'John Doe';
    public const CUSTOMER_PHONE = '+1234567890';
    public const CUSTOMER_LEAD_SOURCE = 'website';

    // Update Customer - Used for testing update operations
    public const UPDATE_CUSTOMER_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0Z2';
    public const UPDATE_CUSTOMER_EMAIL = 'update-customer@example.com';
    public const UPDATE_CUSTOMER_INITIALS = 'Jane Smith';
    public const UPDATE_CUSTOMER_PHONE = '+1234567891';
    public const UPDATE_CUSTOMER_LEAD_SOURCE = 'referral';

    // Delete Customer - Confirmed customer for deletion tests
    public const DELETE_CUSTOMER_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0Z3';
    public const DELETE_CUSTOMER_EMAIL = 'delete-customer@example.com';
    public const DELETE_CUSTOMER_INITIALS = 'Delete Customer';
    public const DELETE_CUSTOMER_PHONE = '+1234567892';
    public const DELETE_CUSTOMER_LEAD_SOURCE = 'social_media';

    // Replace Customer - Used for testing replace operations
    public const REPLACE_CUSTOMER_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0Z4';
    public const REPLACE_CUSTOMER_EMAIL = 'replace-customer@example.com';
    public const REPLACE_CUSTOMER_INITIALS = 'Replace Customer';
    public const REPLACE_CUSTOMER_PHONE = '+1234567893';
    public const REPLACE_CUSTOMER_LEAD_SOURCE = 'email_campaign';

    // Get Customers - Used for testing get operations
    public const GET_CUSTOMER_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0Z5';
    public const GET_CUSTOMER_EMAIL = 'get-customer@example.com';
    public const GET_CUSTOMER_INITIALS = 'Get Customer';
    public const GET_CUSTOMER_PHONE = '+1234567894';
    public const GET_CUSTOMER_LEAD_SOURCE = 'direct';

    // Customer Type - Default type
    public const CUSTOMER_TYPE_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0A1';
    public const CUSTOMER_TYPE_NAME = 'Individual';
    public const CUSTOMER_TYPE_DESCRIPTION = 'Individual customer type for schemathesis testing';

    // Customer Type for Updates
    public const UPDATE_CUSTOMER_TYPE_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0A2';
    public const UPDATE_CUSTOMER_TYPE_NAME = 'Business';
    public const UPDATE_CUSTOMER_TYPE_DESCRIPTION = 'Business customer type for schemathesis testing';

    // Customer Type for Deletion
    public const DELETE_CUSTOMER_TYPE_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0A3';
    public const DELETE_CUSTOMER_TYPE_NAME = 'Enterprise';
    public const DELETE_CUSTOMER_TYPE_DESCRIPTION = 'Enterprise customer type for schemathesis testing';

    // Customer Status - Default status
    public const CUSTOMER_STATUS_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0B1';
    public const CUSTOMER_STATUS_NAME = 'Active';
    public const CUSTOMER_STATUS_DESCRIPTION = 'Active customer status for schemathesis testing';

    // Customer Status for Updates
    public const UPDATE_CUSTOMER_STATUS_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0B2';
    public const UPDATE_CUSTOMER_STATUS_NAME = 'Pending';
    public const UPDATE_CUSTOMER_STATUS_DESCRIPTION = 'Pending customer status for schemathesis testing';

    // Customer Status for Deletion
    public const DELETE_CUSTOMER_STATUS_ID = '01JGVZ9YGXE8P3Q2R5T7W9Y0B3';
    public const DELETE_CUSTOMER_STATUS_NAME = 'Inactive';
    public const DELETE_CUSTOMER_STATUS_DESCRIPTION = 'Inactive customer status for schemathesis testing';
}
