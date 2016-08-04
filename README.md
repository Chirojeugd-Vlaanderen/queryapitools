# queryapitools

This extension helps you creating custom API's that retrieve query result data.
If an external application needs to query CiviCRM in a way that cannot be
expressed using API parameters, you can use this extension to create a custom
API that returns the data from the query, in a way that you can use API
filters, options and joining as always.

The implementation is rather hacky, but it works for CiviCRM 4.7.

Note that this is an alpha version. Things may change siginificantly in the
future. Or the extenstion might just die as well ;-)

## example

A simple example might make things clear.

Suppose you want to create a custom API that returns all households like
`Contact.get` does, but that also returns the number of known household members.

Using SQL, it would work like this:

    SELECT h.*, COUNT(DISTINCT r.contact_id_a) AS n_members
    FROM civicrm_contact h 
    LEFT OUTER JOIN civicrm_relationship r 
      ON r.contact_id_b = h.id 
      AND r.is_active = 1 
      -- household members and head of household
      AND r.relationship_type_id IN (7,8)
    WHERE h.contact_type='Household'
    GROUP BY h.id

Using `queryapitools` you can easily create a custom API in your own extension,
so that returns those results, supporting all the API filtering and option
goodness. This is how your api function might look:

    /**
     * MyHousehold.Get API
     *
     * @param array $params
     * @return array API result descriptor
     * @see civicrm_api3_create_success
     * @see civicrm_api3_create_error
     * @throws API_Exception
     */
    function civicrm_api3_my_household_Get($params) {
      // the SQL query:
      $query = "SELECT h.*, COUNT(DISTINCT r.contact_id_a) AS n_members 
        FROM civicrm_contact h 
        LEFT OUTER JOIN civicrm_relationship r 
          ON r.contact_id_b = h.id 
          AND r.is_active = 1 
          AND r.relationship_type_id IN (7,8)
        WHERE h.contact_type='Household'
        GROUP BY h.id";

      // The API that we are creating will already support all fields of
      // the Contact entity. This is useful in this particular case, but
      // there are probably situations that the fields of the new API are
      // not a superset of the fields of an existing one. I guess I will create
      // a new function in CRM_Queryapitools_Tools for this at some point 
      // in the future.

      // Anyway, for now I need to only declare the additional field 
      // that my API adds to the existing fields of the Contact API:
      $extraFields = array(
        'n_members' => array(
          'name' => 'n_members',
          // 1: integer, 2: string
          'type' => 1, 
          'title' => 'No. of members in household',
        )
      );

      $result = CRM_Queryapitools_Tools::BasicGet(
        // the query used to get the results
        $query, 
        // the params the client passed to the API
        $params, 
        // the BAO to get the existing fields from
        'CRM_Contact_BAO_Contact', 
        // the fields we are adding
        $extraFields);
      return $result;
    }

Now you can use your new API, e.g. with drush

    drush cvapi MyHousehold.get n_members=3 return=display_name,n_members

This will retrieve households with 3 known members, and show their display
names and the number of known members.
