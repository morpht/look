# Look

## Overview
The Look module provides a means to apply a collection of Modifiers to a page 
using various rules. A number of different Modifiers, which are mapped to 
different parts of the page, can be wrapped up to form a coherent Look. The 
Look can then be applied to the page according to url parameters, the path or 
assignment to individual nodes.

The module is comprised of a number of components which work together:
* A Look entity which is fieldable and stores Modifier content.
* A UI which allows for the cascading of Looks to allow for inheritance of 
Modifiers.
* A Service which resoles a single active Look which is applicable to the 
current page
* A mapping configuration which assigns the Look fields to components on the 
page.
* A Conditional Plugin which allows for the assignment of Blocks according to 
Look.
* A Look Switcher Block which allows quick selection of Looks.

The [Modifiers](https://www.drupal.org/project/modifiers) module is a 
dependency. We suggest you also install this module together with 
[Modifiers pack](https://www.drupal.org/project/modifiers_pack) module to get 
up and running quickly.

## Performance
Look entities use the caching capabilities of Drupal 8. Each time you save a 
Look is changed, cache tags are set which invalidate the cached Look and any 
of its children. This ensures that only up to date Looks are used, even if the 
parent looks have been recently changed.

## It's so Convivial
At [convivial.io](https://convivial.io) you can see Look and Modifiers working 
together. Update README files for Look, Modifiers and Modifiers Pack

## Installation
1. The module can be installed via the 
[standard Drupal installation process](http://drupal.org/node/1897420).
2. It will create a new Look entity.

## Usage
1. Add Paragraph fields to the look. eg. field_header_modifiers, 
field_footer_modifiers
2. Select the Paragraph bundles to be made available on these fields. eg. 
color_modifier
2. Define mappings for the Look fields across to page components by defining 
selectors.
3. Create a Look instance, eg. "Default" and add a modifier (colors) to the 
fields.
4. Define a default Look for the site. eg. Default.
5. View pages on the site and confirm that your Look has been applied.

## Maintainers
This module is maintained by developers at Morpht. For more information on
the company and our offerings, see [morpht.com](https://morpht.com/).
