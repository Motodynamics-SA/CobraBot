
@description('The location where the resources will be deployed.')
param location string = 'francecentral'

@description('The name of the application. This will be used to name the resources.')
param appName string = 'price-updater'

@description('The name of the mysql Server.')
param mysqlServerName string = 'laradev'

@description('The SKU for the App Service Plan.')
param appServicePlanSku string = 'B1'

@description('The username for the MySQL database.')
param mysqlUsername string

@description('The password for the MySQL database.')
@secure()
param mysqlPassword string

var appServicePlanName = '${appName}-plan'
var appServiceName = '${appName}-app'
var mysqlDatabaseName = '${appName}_db'

// App Service Plan
resource appServicePlan 'Microsoft.Web/serverfarms@2022-09-01' = {
  name: appServicePlanName
  location: location
  sku: {
    name: appServicePlanSku
    tier: 'Basic'
  }
  properties: {
    reserved: true // Required for Linux
  }
}

// App Service
resource appService 'Microsoft.Web/sites@2022-09-01' = {
  name: appServiceName
  location: location
  identity: {
    type: 'SystemAssigned'
  }
  properties: {
    serverFarmId: appServicePlan.id
    siteConfig: {
      linuxFxVersion: 'PHP|8.2'
      alwaysOn: true
      appSettings: [
        {
          name: 'APP_NAME'
          value: appName
        }
        {
          name: 'APP_ENV'
          value: 'production'
        }
        {
          name: 'APP_DEBUG'
          value: 'false'
        }
        {
          name: 'APP_URL'
          value: 'https://${appServiceName}.azurewebsites.net'
        }
        {
          name: 'APP_KEY'
          value: ''
        }
        {
          name: 'DB_CONNECTION'
          value: 'mysql'
        }
        {
          name: 'DB_HOST'
          value: mysqlServer.properties.fullyQualifiedDomainName
        }
        {
          name: 'DB_PORT'
          value: '3306'
        }
        {
          name: 'DB_DATABASE'
          value: 'laravel'
        }
        {
          name: 'DB_USERNAME'
          value: '${mysqlUsername}@${mysqlServerName}'
        }
        {
          name: 'DB_PASSWORD'
          value: mysqlPassword
        }
        {
          name: 'SCM_DO_BUILD_DURING_DEPLOYMENT'
          value: 'false'
        }
        {
            name: 'WEBSITE_RUN_FROM_PACKAGE'
            value: '1'
        }
        {
            name: 'MYSQL_ATTR_SSL_CA'
            value: '/home/site/wwwroot/AzureMySQLTrustedCACerts.pem'
        }
      ]
      ftpsState: 'FtpsOnly'
    }
  }
}

// MySQL Flexible Server
resource mysqlServer 'Microsoft.DBforMySQL/flexibleServers@2023-12-30' = {
  name: mysqlServerName
  location: location
  sku: {
    name: 'Standard_B1ms'
    tier: 'Burstable'
  }
  properties: {
    administratorLogin: mysqlUsername
    administratorLoginPassword: mysqlPassword
    createMode: 'Default'
    version: '8.0.21'
    storage: {
      storageSizeGB: 20
    }
    backup: {
      backupRetentionDays: 7
      geoRedundantBackup: 'Disabled'
    }
    network: {
      publicNetworkAccess: 'Enabled'
    }
  }
}

// MySQL Database
resource mysqlDatabase 'Microsoft.DBforMySQL/flexibleServers/databases@2023-12-30' = {
  parent: mysqlServer
  name: mysqlDatabaseName
  properties: {
    charset: 'utf8mb4'
    collation: 'utf8mb4_unicode_ci'
  }
}

// Allow Azure services to access the MySQL server
resource mysqlFirewallRule 'Microsoft.DBforMySQL/flexibleServers/firewallRules@2023-12-30' = {
  parent: mysqlServer
  name: 'AllowAzureIPs'
  properties: {
    startIpAddress: '0.0.0.0'
    endIpAddress: '0.0.0.0'
  }
}

output appServiceUrl string = 'https://${appServiceName}.azurewebsites.net'
