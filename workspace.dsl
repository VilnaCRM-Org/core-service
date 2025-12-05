workspace {

    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            coreService = container "Core Service" {

                group "Application" {
                    createCustomerProcessor = component "CreateCustomerProcessor" "Processes HTTP requests for customer creation" "RequestProcessor" {
                        tags "Item"
                    }
                    customerPatchProcessor = component "CustomerPatchProcessor" "Processes HTTP requests for customer updates" "RequestProcessor" {
                        tags "Item"
                    }
                    customerPutProcessor = component "CustomerPutProcessor" "Processes HTTP requests for customer replacement" "RequestProcessor" {
                        tags "Item"
                    }
                    createStatusProcessor = component "CreateStatusProcessor" "Processes HTTP requests for status creation" "RequestProcessor" {
                        tags "Item"
                    }
                    createTypeProcessor = component "CreateTypeProcessor" "Processes HTTP requests for type creation" "RequestProcessor" {
                        tags "Item"
                    }
                    createCustomerCommandHandler = component "CreateCustomerCommandHandler" "Handles CreateCustomerCommand" "CommandHandler" {
                        tags "Item"
                    }
                    updateCustomerCommandHandler = component "UpdateCustomerCommandHandler" "Handles UpdateCustomerCommand" "CommandHandler" {
                        tags "Item"
                    }
                    createStatusCommandHandler = component "CreateStatusCommandHandler" "Handles CreateStatusCommand" "CommandHandler" {
                        tags "Item"
                    }
                    createTypeCommandHandler = component "CreateTypeCommandHandler" "Handles CreateTypeCommand" "CommandHandler" {
                        tags "Item"
                    }
                    healthCheckController = component "HealthCheckController" "Handles health check requests" "Controller" {
                        tags "Item"
                    }
                    dbCheckSubscriber = component "DBCheckSubscriber" "Checks database health" "EventSubscriber" {
                        tags "Item"
                    }
                    cacheCheckSubscriber = component "CacheCheckSubscriber" "Checks cache health" "EventSubscriber" {
                        tags "Item"
                    }
                    brokerCheckSubscriber = component "BrokerCheckSubscriber" "Checks message broker health" "EventSubscriber" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    customer = component "Customer" "Represents a customer aggregate" "Entity" {
                        tags "Item"
                    }
                    customerStatus = component "CustomerStatus" "Represents customer status" "Entity" {
                        tags "Item"
                    }
                    customerType = component "CustomerType" "Represents customer type" "Entity" {
                        tags "Item"
                    }
                    healthCheckEvent = component "HealthCheckEvent" "Represents a health check event" "DomainEvent" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    mongoCustomerRepository = component "MongoCustomerRepository" "Manages access to customers" "Repository" {
                        tags "Item"
                    }
                    mongoStatusRepository = component "MongoStatusRepository" "Manages access to statuses" "Repository" {
                        tags "Item"
                    }
                    mongoTypeRepository = component "MongoTypeRepository" "Manages access to types" "Repository" {
                        tags "Item"
                    }
                    eventBus = component "InMemorySymfonyEventBus" "Handles event publishing" "EventBus" {
                        tags "Item"
                    }
                }

                database = component "Database" "Stores application data" "MongoDB" {
                    tags "Database"
                }
                cache = component "Cache" "Caches application data" "Redis" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "Handles asynchronous messaging" "AWS SQS" {
                    tags "Database"
                }

                createCustomerProcessor -> createCustomerCommandHandler "dispatches CreateCustomerCommand"
                customerPatchProcessor -> updateCustomerCommandHandler "dispatches UpdateCustomerCommand"
                customerPutProcessor -> updateCustomerCommandHandler "dispatches UpdateCustomerCommand"
                createCustomerCommandHandler -> customer "creates"
                updateCustomerCommandHandler -> customer "updates"
                createCustomerCommandHandler -> mongoCustomerRepository "persists via"
                updateCustomerCommandHandler -> mongoCustomerRepository "uses"
                mongoCustomerRepository -> customer "save and load"
                mongoCustomerRepository -> database "accesses data"

                createStatusProcessor -> createStatusCommandHandler "dispatches CreateStatusCommand"
                createStatusCommandHandler -> customerStatus "creates"
                createStatusCommandHandler -> mongoStatusRepository "persists via"
                mongoStatusRepository -> customerStatus "save and load"
                mongoStatusRepository -> database "accesses data"

                createTypeProcessor -> createTypeCommandHandler "dispatches CreateTypeCommand"
                createTypeCommandHandler -> customerType "creates"
                createTypeCommandHandler -> mongoTypeRepository "persists via"
                mongoTypeRepository -> customerType "save and load"
                mongoTypeRepository -> database "accesses data"

                healthCheckController -> healthCheckEvent "creates"
                healthCheckEvent -> dbCheckSubscriber "triggers"
                healthCheckEvent -> cacheCheckSubscriber "triggers"
                healthCheckEvent -> brokerCheckSubscriber "triggers"
                dbCheckSubscriber -> database "checks"
                cacheCheckSubscriber -> cache "checks"
                brokerCheckSubscriber -> messageBroker "checks"

                healthCheckController -> eventBus "publishes via"
                eventBus -> healthCheckEvent "dispatches"
            }
        }
    }

    views {
        component softwareSystem.coreService "Components_All" {
            include *
        }

        styles {
            element "Item" {
                color white
                background #34abeb
            }
            element "Database" {
                color white
                shape cylinder
                background #34abeb
            }
        }
    }
}
