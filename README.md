# Let's Wi-fi Portal Server

This is the reference portal server for Let's Wi-Fi, geteduroam and getgovroam.
It's function is to issue certificates (“pseudo-credentials”) after user authentication,
which can be used to connect to a wireless network such as eduroam, govroam or OpenRoaming.

The software provides a portal for users to obtain certificates embedded in configuration profiles,
as well as an API that's used by various apps to configure a wireless network directly on the device.
We recommend to use the apps where possible, and the portal will encourage users to do so.

User authentication before issuing a certificate is done through [SimpleSAMLphp](https://www.simplesamlphp.org),
which can be configured with various identity providers.

* [Features](FEATURES.md)
* [Contributing](CONTRIBUTE.md)
* [Deployment considerations](DEPLOY.md)
* [Production installation](INSTALL.md)

See https://eduroam.app for more information.
