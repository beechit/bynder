This is the Bynder DAM integration for TYPO3
====

Bynder integration extension, providing seamless access to Bynder's asset bank on your website.

The extension will allow the authorised users to:

- Select/import images from Bynder including metadata (title, description, etc)
- Display images (scaled/cropped) from Bynder


### Requirements:

1) You are a customer of Bynder https://www.bynder.com/trial/
2) You have API access (tokens) https://domain.getbynder.com/pysettings#api


### Installation:

Download and install `bynder` through extension manager in the back-end of you TYPO3 installation.

Or add `byder` via composer `composer require beechit/bynder` to your existing
project and go the the extension manager in the back-end to install it.

Next got the the extension configuration of EXT:bynder and fill in the needed url's and credentials.


### TODO:

- Raise dependencies and remove work around's after https://forge.typo3.org/issues/84069 and https://forge.typo3.org/issues/84068 are released 
- Storage `is_browsable` is now checked on permission check in the core but should only be handled as GUI check 
- Load/cache derivative from API and use these in `AssetProcessing::getThumbnailInfo()`
- Add warning if selected file isn't allowed
- Add warning when more then allowed number of files are added
 

### How to contribute

- Fork the repository
- Create a new branch with your feature or fix
- Make sure to run php-cs-fixer over your code
- Push changes to your branch
- Create a pull request to this repository


### Development

- When updating/changing composer requirements don't forget to update the composer.json in the private directory. 
- To build new TER package run `composer run-script package`. 
  
  A bynder.zip is created, this file can be uploaded in the extension manager.


### Need help with integration?

- Contact support@beech.it
 

 