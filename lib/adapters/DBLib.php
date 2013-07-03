<?php

require_once dirname(__FILE__) . DS . 'Interface.php';
require_once dirname(__FILE__) . DS . 'PDO.php';

/**
 * Adapter Class for SQL Server 2008.
 *
 * @author Drew J. Sonne <drew.sonne@gmail.com>
 */
class DBV_Adapter_DBLib extends DBV_Adapter_PDO
{

    protected function _buildDsn($host = false, $port = false, $database_name = false)
    {
        return "dblib:host=$host;dbname=$database_name";
    }

    public function _getTablesQuery()
    {
        return $this->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.tables");
    }

    protected function _getViewsQuery()
    {
        return $this->query("
            SELECT
                name AS view_name,
                'VIEW'
            FROM sys.views;
        ");
    }

    protected function _getTriggersQuery()
    {
        return $this->query("
            SELECT
                 sysobjects.name AS trigger_name
            FROM sysobjects

            INNER JOIN sysusers
                ON sysobjects.uid = sysusers.uid

            INNER JOIN sys.tables t
                ON sysobjects.parent_obj = t.object_id

            INNER JOIN sys.schemas s
                ON t.schema_id = s.schema_id

            WHERE sysobjects.type = 'TR';
            ");
    }

    protected function _getFunctionsQuery()
    {
        return $this->query("
            SELECT
                object_id, name
            FROM
                sys.objects
            WHERE
                type IN ('FN', 'IF', 'TF');
        ");
    }

    /**
     * Get all the procedures, which are not
     * @return PDOStatement
     */
    protected function _getProceduresQuery()
    {
        return $this->query("SELECT 0, name FROM SYS.ALL_OBJECTS WHERE is_ms_shipped = 0 AND type='P' AND NAME NOT LIKE 'sp_MS%';");
    }

    public function getSchemaObject($name)
    {
        $statement = $this->_connection->prepare("
            SELECT
                'CREATE TABLE [' + so.name + '] (' + o.list + ')' + CASE WHEN tc.Constraint_Name IS NULL THEN ''
                ELSE
                'ALTER TABLE ' + so.Name + ' ADD CONSTRAINT ' + tc.Constraint_Name  + ' PRIMARY KEY ' + ' (' + LEFT(j
                .List, Len(j.List)-1) + ')' END
            FROM sysobjects so
                CROSS APPLY (
                    SELECT
                        '  ['+column_name+'] ' +
                        data_type + case data_type
                            when 'sql_variant' then ''
                            when 'text' then ''
                            when 'ntext' then ''
                            when 'decimal' then '(' + cast(numeric_precision as varchar) + ', ' + cast(numeric_scale as varchar) + ')'
                            else coalesce('('+case when character_maximum_length = -1 then 'MAX' else cast(character_maximum_length as varchar) end +')','') end + ' ' +
                        case when exists (
                        select id from syscolumns
                        where object_name(id)=so.name
                        and name=column_name
                        and columnproperty(id,name,'IsIdentity') = 1
                        ) then
                        'IDENTITY(' +
                        cast(ident_seed(so.name) as varchar) + ',' +
                        cast(ident_incr(so.name) as varchar) + ')'
                        else ''
                        end + ' ' +
                         (case when IS_NULLABLE = 'No' then 'NOT ' else '' end ) + 'NULL ' +
                          case when information_schema.columns.COLUMN_DEFAULT IS NOT NULL THEN 'DEFAULT '+ information_schema.columns.COLUMN_DEFAULT ELSE '' END + ', '

                     from information_schema.columns where table_name = so.name
                     order by ordinal_position
                    FOR XML PATH('')) o (list)
            LEFT JOIN information_schema.table_constraints tc
            ON tc.Table_name = so.Name AND tc.Constraint_Type  = 'PRIMARY KEY'
            CROSS APPLY(
                select '[' + Column_Name + '], '
                 FROM   information_schema.key_column_usage kcu
                 WHERE  kcu.Constraint_Name = tc.Constraint_Name
                 ORDER BY
                    ORDINAL_POSITION
                 FOR XML PATH('')) j (list)
            where xtype = 'U'
            AND name NOT IN ('dtproperties')
            AND so.name = :objectName;
        ");

        $statement->execute(array('objectName' => $name));

        $result = $statement->fetchAll(PDO::FETCH_NUM);

        if (count($result) > 0 && count($result[0]) > 0) {
            return $result[0][0];
        } else {
            throw new DBV_Exception("Create query could not be created.");
        }
    }
}