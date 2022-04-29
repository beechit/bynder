This is the Bynder DAM integration for TYPO3
====

Bynder integration extension, providing seamless access to Bynder's asset bank on your website.

The extension will allow the authorised users to:

- Select/import images from Bynder including metadata (title, description, etc)
- Display images (scaled/cropped) from Bynder


### Requirements

1) You are a customer of Bynder https://www.bynder.com/trial/
2) You have API access (permanent tokens) https://domain.getbynder.com/pysettings/permanent-tokens/


### Installation

Download and install `bynder` through extension manager in the back-end of you TYPO3 installation.

Or add `byder` via composer `composer require beechit/bynder` to your existing
project and go the the extension manager in the back-end to install it.

Next got the the extension configuration of EXT:bynder and fill in the needed url's and credentials.

#### Available Configuration

| Key               | Description                                                                                      | Required | Default                                                |
| ----------------: |--------------------------------------------------------------------------------------------------|:--------:| ------------------------------------------------------ |
| url               | Bynder Url (example: domain.bynder.com)                                                          |   Yes    |                                                        |
| permanent_token   | Bynder API: Permanent token                                                                      |   Yes    |                                                        |
| image_unavailable | Displayed image when file is not retrievable like when the file status is deleted or unpublished |   *No*   | EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg |


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
 

