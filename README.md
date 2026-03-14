# ElielWeb VisualCompositor

Module Magento 2.4.8 + Hyvä — Moteur de composition d'image dynamique pour produits simples RedLine.

## Fonctionnement

Superpose plusieurs couches PNG transparentes pour composer l'image produit dynamiquement selon les options choisies par le client (couleur du fil, couleur de l'or, etc.).

## Architecture

- **Famille** : groupe de produits partageant la même structure de couches (ex: `bracelet_fil`)
- **Couche** : un calque PNG (fixe ou variable selon une option)
- **Mapping** : association option_value → fichier PNG

## Structure des images
```
pub/media/compositor/
└── families/
    └── bracelet_fil/
        ├── wire/        # couche fil (variable selon wire_color)
        ├── setting/     # couche serti (variable selon gold_color)
        └── clasp/       # couche fermoir (variable selon gold_color)
```

## Activation par produit

Activer l'attribut `dynamic_image_enabled` sur le produit + associer le produit à une famille via la table `elielweb_compositor_product`.

## Compatibilité

- Magento 2.4.8
- PHP 8.1+
- Hyvä Theme
- Mobile first

## Changelog

### 1.0.0
- Moteur de composition Canvas JS
- Gestion familles, couches, mappings
- Attribut `dynamic_image_enabled`
- Overlay galerie Hyvä
- Mobile first / ResizeObserver
