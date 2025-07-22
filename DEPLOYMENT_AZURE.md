# Deploying to Azure from a Local Machine

This guide provides a step-by-step process for deploying the Laravel application to Azure directly from your local development environment.

## Prerequisites

- [Azure CLI](https://docs.microsoft.com/en-us/cli/azure/install-azure-cli) must be installed and configured.
- PHP, Composer, Node.js, and NPM must be installed on your local machine.
- A ZIP utility must be available.

---

## Step 1: Login to Azure

First, authenticate your local machine with your Azure account. This will open a browser window for you to complete the login process.

```bash
az login
```

---

## Step 2: Deploy or Update Azure Infrastructure

This command uses the Bicep template located in the `/azure` directory to provision or update all the necessary cloud resources (App Service, MySQL Database, etc.).

1. **Create the Resource Group**: If it doesn't already exist, create it first.

    ```bash
    az group create --name rg-price-updater --location francecentral
    ```

2. **Run the Bicep Deployment**: Execute the following command. Because the `mysqlPassword` parameter in the Bicep file is marked as `@secure`, the Azure CLI will securely prompt you to enter the password in the terminal. This prevents it from being saved in your shell history.

    ```bash
    az deployment group create \
      --resource-group rg-price-updater \
      --template-file azure/main.bicep \
      --parameters @azure/params.json
    ```

---

## Step 3: Deploy the Application Code

In order to deploy the Application code, run the deployment script:

```bash
./azure/azure-deploy.sh
```

---

## Step 4: Run Post-Deployment Commands

After the code is deployed, you need to run database migrations and other setup commands on the production server. Use `az webapp ssh` to execute commands remotely.

1. **Run Database Migrations**:

    ```bash
    az webapp ssh \
      --resource-group rg-price-updater \
      --name price-updater-app \
      --command "php artisan migrate --force"
    ```

    *The `--force` flag is required to run migrations in a non-interactive production environment.*

2. **(Optional) Run Database Seeders**:
    If you need to seed your production database, run the seeder command. **Use with caution.**

    ```bash
    az webapp ssh \
      --resource-group rg-price-updater \
      --name price-updater-app \
      --command "php artisan db:seed --class=YourProductionSeeder --force"
    ```

