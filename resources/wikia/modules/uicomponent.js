/**
 * JS version of Component.class.php - part of UI repo API for rendering components
 *
 * UIComponent handles rendering component
 *
 * @author Rafal Leszczynski <rafal@wikia-inc.com>
 *
 */

define('wikia.uicomponent',['wikia.mustache'], function uicomponent(mustache) {
	'use strict';

	function UIComponent() {

		var componentConfig,
			componentType,
			componentVars,
			// preventing this from pointing to Global Object if by accident UIComponent is called without "new"
			that = (!this instanceof UIComponent) ? {} : this;

		/**
		 * Set template name for rendering this component
		 *
		 * @param {String} type name of the template
		 */

		function setComponentType(type) {
			componentType = type;
		}

		/**
		 * Return template name set for rendering this component
		 *
		 * @return {String} name of the template
		 */

		function getComponentType() {
			return componentType;
		}

		/**
		 * Set mustache template for rendering this component
		 *
		 * @param {{}} vars object with mustachevariables
		 */

		function setComponentVars(vars) {
			componentVars = vars;
		}

		/**
		 * Return mustache variables set for rendering this component
		 *
		 * @return {{}} object with variables
		 */

		function getComponentVars() {
			return componentVars;
		}

		/**
		 * Return mustache template
		 *
		 * @param {String} type name of the template
		 *
		 * @return {String} html markup for the component
		 */

		function getTemplate(type) {
			return componentConfig['templates'][type];
		}

		/**
		 * Check if all required mustache variables are set
		 *
		 * @throw {Error} message with missing variables
		 */

		function validateComponent() {
			var requiredVars = componentConfig['templatesVars'][getComponentType()]['required'],
				missingVars= [];

			requiredVars.forEach(function(element) {
				if (!componentVars.hasOwnProperty(element)) {
					missingVars.push(element);
				}
			});

			if (missingVars.length > 0) {
				var variables = missingVars.join(', ');
				throw new Error('Missing required mustache variables: ' + variables + '!');
			}
		}

		/**
		 * Renders component
		 *
		 * @param {{}} params (example: { type: [template_name], vars: { [mustache_variables] } }
		 *
		 * @return {String} html markup for the component
		 */

		that.render = function(params) {

			setComponentType(params['type']);
			setComponentVars(params['vars']);

			validateComponent();

			return mustache.render(getTemplate(getComponentType()), getComponentVars());
		};

		/**
		 * Configures component
		 *
		 * @param {{}} templates object with mustache templates
		 * @param {{}} templateVars object with accepted template variables
		 */

		that.setComponentsConfig = function(templates, templateVars) {
			componentConfig['templates'] = templates;
			componentConfig['templatesVars'] = templateVars;
		};

		return that;

	}

	return UIComponent;

});
