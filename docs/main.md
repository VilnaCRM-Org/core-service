## What Is Core Service?

The VilnaCRM Core Service is designed to manage customer data and relationships within the VilnaCRM ecosystem. It provides essential functionalities such as customer management, customer type classification, and customer status tracking, implemented with REST API and GraphQL, ensuring seamless integration with other components of the CRM system.

## What Is Core Service For?

Core Service is a fully functional microservice designed to manage customer relationships within a modern PHP application ecosystem. It is a critical component in any CRM system requiring customer management capabilities, providing a scalable solution for handling customer data and business processes.

This service is particularly beneficial for applications that need to:

- **Manage customers**: Offering a streamlined and customizable customer registration and management process.
- **Classify customers**: Categorizing customers by type (e.g., Individual, Business, Enterprise).
- **Track customer lifecycle**: Managing customer statuses to track their journey through the sales pipeline.
- **Integrate with other services**: Easily connecting with other microservices or external systems for a cohesive ecosystem.

By leveraging the Core Service, developers and organizations can significantly reduce the time and effort required to implement robust customer management functionality, allowing them to focus on developing the unique features of their applications.

## What Design Principles Underlie Core Service?

Core Service is built on several key design principles:

- **Hexagonal Architecture**: Ensures the separation of concerns by isolating the application's core logic from external influences.
- **Domain-Driven Design**: Focuses on the core domain logic, making the system more understandable and flexible.
- **CQRS**: Separates the read and write operations to improve performance, scalability, and security.
- **Modern PHP Stack**: Utilizes the latest PHP features and best practices to ensure a high-quality, maintainable codebase.
- **Event-Driven Architecture**: Utilizes an event-driven approach to handle domain actions, making the system highly responsive and scalable.

## What Problem Does Core Service Solve?

- **Modern PHP Stack Integration**: By providing a template that leverages a modern PHP stack, the core service aims to streamline the development of PHP services, ensuring they are built on a solid, up-to-date foundation.
- **Built-in Docker Environment**: The challenge of setting up consistent development environments across different machines is solved by providing a Docker-based setup. This ensures that service can be developed, tested, and deployed with the same configurations, reducing "works on my machine" problems.
- **Convenient Make CLI Commands**: The service solves the problem of remembering and managing multiple commands for different tasks by providing a `make` command interface. This simplifies the process of building, running, and managing the application.

## Key Features

- **Customer Management**: Facilitates adding, updating, and removing customers in the application, including validation workflows.
- **Customer Type Classification**: Provides categorization of customers by business type for better organization and targeting.
- **Customer Status Tracking**: Enables tracking customer lifecycle stages through customizable statuses.
- **Flexibility**: Core Service functionality implemented with REST API and GraphQL to provide a versatile platform for interacting with it.
- **Date-based Filtering**: Supports filtering customers by creation and update dates with various operators.
- **Sorting Capabilities**: Allows sorting customer collections by multiple fields.

## Code Quality

To maintain a high standard of code quality, Core Service includes:

- Static analysis tools, such as PHPInsights and Psalm to help developers ensure code quality, identify potential issues, and enforce best practices in a project.
- Testing tools like PHPUnit and Behat for comprehensive test coverage and robustness of a PHP application.
- Mutation testing tools represented by Infection, to ensure the quality of tests.
- Load testing tools represented by K6, to ensure optimal performance.
- Continuous Integration (CI) checks.

## Architecture Diagram

When running the service locally, you can view interactive diagrams at [http://localhost:8080/workspace/diagrams](http://localhost:8080/workspace/diagrams).

Also, check [this link](https://structurizr.com/) to learn about the tool we used to create this diagram.

Learn more about [Getting Started](getting-started.md).
