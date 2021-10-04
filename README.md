<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Elastic Email for Craft CMS icon"></p>

<h1 align="center">Elastic Email for Craft CMS</h1>

This plugin provides a [Elastic Email](http://www.elasticemail.com/) integration for [Craft CMS](https://craftcms.com/).

Please make sure to purchase a license from Craft CMS [plugin store](https://plugins.craftcms.com/elasticemail).

## Requirements

This plugin requires Craft CMS 3.1.5 or later.

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Elastic Email”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require workwithtom/elasticemail

# tell Craft to install the plugin
./craft install/plugin elasticemail
```

## Setup

Once Elastic Email is installed, go to Settings → Email, and change the “Transport Type” setting to “Elastic Email”. 
Enter your Elastic Email endpoint and API Key (which you can get from [www.elasticemail.com](https://www.elasticemail.com/) page).

> **Tip:** The API Key, and Endpoint settings can be set to environment variables. See [Environmental Configuration](https://docs.craftcms.com/v3/config/environments.html) in the Craft docs to learn more about that.