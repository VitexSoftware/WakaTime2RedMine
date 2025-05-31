## How to Obtain Your WakaTime Oauth Token

use the following steps to obtain your WakaTime OAuth token:

[Create an app](https://wakatime.com/apps) ,then fill in the required fields:

App Name:             WorkHours2Redmine or any name you prefer
Publisher Name:       VitexSoftware in this case
Publisher Website:    https://vitexsoftware.com but you can use your own website
Description:          import work hours from WakaTime to Redmine
Install URL:          http://localhost/wakatime2redmine/ouauth.php
Redirect URI:         http://localhost/wakatime2redmine/redirect.php

After creating the app, open Install URL in your browser, and you will be redirected to the WakaTime authorization page.
Click on "Authorize" to grant access to your WakaTime data. After authorization, you will be redirected to the Redirect URI you specified.
You will see a message with your OAuth token. Copy this token and save it securely, as you will need it to configure the WakaTime integration in your application.

**Note:** Keep your API key secure and do not share it publicly.

## How to Use the WakaTime Oauth Token

put the token in the [`.env`](example.env) file in the project root directory.

```env
# .env file in wakatime2redmine directory

APP_DEBUG=true
EASE_LOGGER=console
WAKATIME_TOKEN=waka_tok_F3CM8WUJr7LmBfDWz4kmd4DNv8crsme1BNVX2P45CemFxXmlMoLJkaKddXvNf6lGt4m3aoQOkFQ9Na88
REDMINE_USERNAME=3da54e210542739045e9beed6a3f8d041be7dd93
REDMINE_PASWORD=
REDMINE_URL=https://redmine.vitexsoftware.com/
REDMINE_PROJECT=test

```
