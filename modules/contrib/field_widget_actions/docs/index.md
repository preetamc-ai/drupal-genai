# Field Widget Actions Module

## What is the Field Widget Actions module?

The Field Widget Actions module provides an easy way to attach action buttons to form fields.

The module doesn't do anything by itself, but is a builder module that allows other modules to provide processors that can be used to trigger processes on form 
fields that fills out the field or gives suggestions on how to fill out the field.

This works with any field as long as the processor is configured to work with that field type and widget.

## Dependencies

The Field Widget Actions module can be installed by itself, but it does require a processor to be installed to be actually useful.

It also requires the Field UI module to be installed if you want to configure the field widget actions in the UI - however, you can always run the 
configured field widget actions without the Field UI module if they have been setup.

## Known processors
You can click on the links in the menu to see how to configure the processors for different field types. But the following processors are known:

* AI Automators
* AI Content Suggestions
* [ECA](https://ecaguide.org/plugins/eca/base/events/eca_base_eca_field_widget/)
* AI Agents
* Custom field
