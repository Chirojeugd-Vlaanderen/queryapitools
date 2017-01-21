<?php
/*
  be.chiro.civi.queryapitools - tools for creating API's based on query results.
  Copyright (C) 2016, 2017  Chirojeugd-Vlaanderen vzw

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Just a static function for the moment.
 *
 * @author johanv
 */
class CRM_Queryapitools_Tools {
  /**
   * Basic API-get-operation for query results.
   * 
   * @param string $query Query that provides all data.
   * @param array $params parameters for the API call.
   * @param string $entity The API will be called as if this is a get-
   *                           operation on this kind of entity. This is relevant
   *                           for ACL's, permissions and custom fields,
   *                           I guess.
   * @param array $extraFields Field specs for columns your query will return, 
   *                           in addition to the standard fields provided by the
   *                           BAO.
   * @return array API result array.
   * 
   * TODO: In the long run, we should try to determine $fieldNames dynamically.
   */
  public static function BasicGet($query, $params, $entity, $extraFields) {
    // I copied this more or less from _civicrm_api3_basic_get in api/v3/utils.php.
    $options = _civicrm_api3_get_options_from_params($params);
    $query = new CRM_Queryapitools_SelectQuery($query, $params, $entity, $extraFields);
    $query->where = $params;

    if ($options['is_count']) {
      $query->select = array('count_rows');
    }
    else {
      $query->select = array_keys(array_filter($options['return']));
      $query->orderBy = $options['sort'];
    }
    $query->limit = $options['limit'];
    $query->offset = $options['offset'];

    $result = $query->run();
    return civicrm_api3_create_success($result, $params, NULL, 'get');
  }
}
