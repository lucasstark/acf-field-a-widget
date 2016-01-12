-----------------------

# ACF A Widget Field

A Field for selecting an existing widget and modifying the widget values directly on a post or page.  This is useful if you have widgets that you would like to allow users to add to a page but don't want to create special sidebars for the purpose.  

-----------------------

### Description

A Field for selecting an existing widget and modifying the widget values directly on a post or page.  This is useful if you have widgets that you would like to allow users to add to a page but don't want to create special sidebars for the purpose.  

Just create a field group, add a "A Widget" field and select which widget you'd like to enable.   The users will then see the widget form and will be able to modify the values directly without having to deal with sidebars.   

A typical use case would be to create a Flexibile Field group and add a bunch of A Widget fields for the various widgets you'd like to enable for the content authors.  Content authors can build out a "sidebar" without ever leaving your page or post. 

You'll still need to handle the rendering of these widgets in your template.  A helper function is provided acf_a_widget_the_field($field_value).   Call that function with the value of this field and it will render out the assoicated widget for you. 


### Compatibility

This ACF field type is currently compatible with:
* ACF 5


### Installation

1. Copy the `acf-field-a-widget` folder into your `wp-content/plugins` folder
2. Activate the A Widget plugin via the plugins admin page
3. Create a new field via ACF and select the A Widget type
4. Please refer to the description for more info regarding the field type settings


