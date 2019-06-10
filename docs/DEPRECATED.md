## Подключение SteamBoatEngine как пакета из gitlab-репозитория:

Старое подключение файлов файлов движка SteamBoatEngine
```
"autoload": {
      "psr-4": {
        "SteamBoat\\"     : "engine/SteamBoat/"
      }
    },

```

Локальный репо:
```
 "repositories": [
      {
        "type": "package",
        "package": {
          "name": "karelwintersky/steamboatengine",
          "version": "1.9",
          "source": {
            "url": "http://gitlab.dev.ajur/wombat/steamboatengine.git",
            "type": "git",
            "reference": "master"
          },
          "autoload": {
            "classmap": ["sources"]
          }
        }
      }
    ],
    "require": {
    	"karelwintersky/steamboatengine": "*" 
    }
```