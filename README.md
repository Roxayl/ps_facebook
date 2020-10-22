# ps_facebook

## Installation
Use `make docker-build` to install dependencies.

Check other commands with `make`.

## Requirements
You need a facebook developper account added to the PrestaShop Social Media app (ID:726899634800479).

## Usage
Install module and connect to FBE in module BO

## Event Pixel not supported on PrestaShop 1.6

```
CustomizeProduct
```

link of all event available for pixel <a href="https://developers.facebook.com/docs/facebook-pixel/reference/" target="_blank">here</a>

### Params

```
{
  "data": [
    {
      "business_manager_id": "166543204931345",
      "pixel_id": "631797804378534",
      "profiles": [
        "104615824667686"
      ],
      "catalog_id": "593667111582174",
      "pages": [
        "104615824667686"
      ]
    }
  ]
}
```