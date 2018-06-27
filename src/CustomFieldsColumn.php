<?php

namespace CustomFields;

/**
 * Add and manages column for custom field type.
 */
class CustomFieldsColumn {

  /**
   * CustomFieldsType object of type with column to be declared.
   *
   * @var \CustomFields\CustomFieldsType
   */
  protected $cfType;

  /**
   * Machine name of column.
   *
   * @var string
   */
  protected $column;

  /**
   * The camelCase version of column name.
   *
   * @var string
   */
  protected $columnCamelCase;

  /**
   * Column definition.
   *
   * @var array
   */
  protected $columnInfo;

  /**
   * Callback to populate each row of column.
   *
   * @var callable
   */
  protected $columnContentMethod;

  /**
   * Construct object for column, and set up hooks to manage it.
   *
   * @param CustomFieldsType $cfType
   *   CustomFieldsType object of type with column to be declared.
   * @param string $column
   *   Machine name of column.
   * @param array $columnInfo
   *   Column definition.
   */
  public function __construct(CustomFieldsType $cfType, string $column, array $columnInfo) {
    $this->cfType = $cfType;
    $this->column = $column;
    $this->columnCamelCase = CustomFieldsUtilities::makeCamelCase($column);
    $this->columnInfo = $columnInfo;
    $singularName = $cfType->getSingularName();
    // Add columns.
    add_filter('manage_' . $singularName . '_posts_columns', [$this, 'addColumn']);
    // Populate columns.
    if (method_exists($this->cfType->getObject(), $this->columnCamelCase . 'ColumnContent')) {
      $this->columnContentMethod = function ($id) {
        return $this->cfType->getObject()->{$this->columnCamelCase . 'ColumnContent'}($id, $this);
      };
    }
    else {
      $this->columnContentMethod = function ($id) {
        return $this->defaultColumnContent($id);
      };
    }
    add_action('manage_' . $singularName . '_posts_custom_column', [$this, 'addColumnContent'], 10, 2);
    // Optionally make columns sortable.
    if (!empty($columnInfo['sort'])) {
      add_filter('manage_edit-' . $singularName . '_sortable_columns', [$this, 'makeColumnSortable']);
      if (method_exists($this->cfType->getObject(), $this->columnCamelCase . 'ColumnSort')) {
        $columnSortMethod = function ($wpQuery) {
          $this->cfType->getObject()->{$this->columnCamelCase .
            'ColumnSort'}($wpQuery, $this->column, $this->columnInfo);
        };
      }
      else {
        $columnSortMethod = function ($wpQuery) {
          $this->defaultColumnSort($wpQuery, $this->column, $this->columnInfo);
        };
      }
      // @TODO Consider adding option to call custom method with filter
      // 'posts_clauses' instead of this action, based on value in
      // $columnInfo['sort'].  That would allow more complex sorting. If done,
      // filter should call a closure similar to the one below for the action
      // 'pre_get_posts', making sure custom function would only be called for
      // queries related to columns (i.e. don't leave that requirement up to the
      // custom function itself).
      add_action('pre_get_posts', function ($wpQuery) use ($columnSortMethod) {
        if (is_admin() && $wpQuery->is_main_query() && $wpQuery->get('orderby') == $this->column) {
          ($columnSortMethod)($wpQuery);
        }
      });
    }
  }

  /**
   * Callback to add and position column.
   *
   * @param array $columns
   *   Array of columns to print.
   *
   * @return array
   *   Array of columns to print with new column added in place.
   */
  public function addColumn(array $columns) {
    $columnInfo = $this->columnInfo;
    // Default to added columns sequentially between post title and author.
    $position = empty($columnInfo['position']) ? -2 : (int) $columnInfo['position'];
    $new_column = array($this->column => $columnInfo['title']);
    $columns = array_slice($columns, 0, $position, TRUE) +
      $new_column + array_slice($columns, $position, NULL, TRUE);
    return $columns;
  }

  /**
   * Callback to output content into column for each row.
   *
   * @param string $column
   *   Machine name of column.
   * @param int $id
   *   Wordpress post ID.
   */
  public function addColumnContent(string $column, int $id) {
    if ($column == $this->column) {
      echo ($this->columnContentMethod)($id);
    }
  }

  /**
   * Get default column content, assuming column name is post meta name.
   *
   * @param int $id
   *   Wordpress post ID.
   *
   * @return string
   *   HTML to output into column.
   */
  protected function defaultColumnContent(int $id) {
    return esc_html($this
      ->cfType
      ->getCfs()
      ->getStorage()
      ->retrieve($id, $this->column));
  }

  /**
   * Callback to set column as sortable.
   *
   * @param array $columns
   *   Array of column names, also keyed by column names.
   *
   * @return array
   *   Array of column names, also keyed by column names.
   */
  public function makeColumnSortable(array $columns) {
    $columns[$this->column] = $this->column;
    return $columns;
  }

  /**
   * Default callback to sort column by post meta with key matching column name.
   *
   * This assumes Wordpress "meta data" is used for storage.
   *
   * @param \WP_Query $wpQuery
   *   Wordpress' WP_Query instance used to query pages for admin screen.
   * @param string $column
   *   Column name.
   * @param array $columnInfo
   *   Column definition.
   *
   * @TODO document that changing the storage class requires using a custom sort.
   */
  protected function defaultColumnSort(\WP_Query $wpQuery, string $column, array $columnInfo) {
    $wpQuery->set('orderby', 'meta_value');
    // Orderby meta key, defaults to column name.
    if (empty($columnInfo['orderby'])) {
      $columnInfo['orderby'] = $column;
    }
    $wpQuery->set('meta_key', $columnInfo['orderby']);
    // Default order is ASC.
    $order = (empty($_GET['order']) || strtoupper($_GET['order']) != 'DESC') ?
      'ASC' : 'DESC';
    $wpQuery->set('order', $order);
  }

}
