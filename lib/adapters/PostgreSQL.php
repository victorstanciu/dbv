<?php
require_once dirname(__FILE__) . DS . 'Interface.php';

/**
 * Class DBV_Adapter_PostgreSQL
 * This class create the function "generate_create_statement" in Postgres
 * compensate for the lack of MySQL functions "SHOW CREATE"
 * @author Marcelo Rodovalho <marcelo2208@gmail.com>
 */
class DBV_Adapter_PostgreSQL implements DBV_Adapter_Interface
{
    /**
     * @var PDO
     */
    protected $connection;

    /**
     * @var string
     */
    protected $schema = 'public';

    /**
     * @param bool|false $host
     * @param bool|false $port
     * @param bool|false $username
     * @param bool|false $password
     * @param bool|false $database_name
     * @throws DBV_Exception
     */
    public function connect($host = false, $port = false, $username = false, $password = false, $database_name = false)
    {
        $this->database_name = $database_name;

        try {
            $this->connection = new PDO("pgsql:dbname=$database_name;host=$host", $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->query('SET search_path TO ' . $this->schema);
            $this->query($this->dropGenerateCreateStatement());
            $this->query($this->generateCreateStatement());
        } catch (PDOException $e) {
            throw new DBV_Exception($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * @return string
     */
    protected function dropGenerateCreateStatement()
    {
        return "DROP FUNCTION IF EXISTS generate_create_statement(type_name text, tablename text, schema_name text);";
    }

    /**
     * @return string
     */
    protected function generateCreateStatement()
    {
        return <<<EOF
CREATE OR REPLACE FUNCTION generate_create_statement(type_name text, tablename text, schema_name text DEFAULT 'public')
  RETURNS text AS
\$BODY\$
    DECLARE
        return_value   text := '';
        temp_value     text := '';
        temp_const     text := '';
        temp_index     text := '';
        column_record  record;
        const_record   record;
        index_record   record;
        indexes_not_in oid [];
        oidcheck       boolean;
    BEGIN
        IF type_name = 'table' THEN
            /*
             * Generate Table Statement
             * + Constraints
             * + Indexes
             */
            FOR column_record IN
                SELECT
                    b.nspname as schema_name,
                    b.relname as table_name,
                    a.attname as column_name,
                    pg_catalog.format_type(a.atttypid, a.atttypmod) as column_type,
                    CASE WHEN
                    (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
                     FROM pg_catalog.pg_attrdef d
                     WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef) IS NOT NULL THEN
                        'DEFAULT '|| (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128)
                              FROM pg_catalog.pg_attrdef d
                              WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef)
                    ELSE
                        ''
                    END as column_default_value,
                    CASE WHEN a.attnotnull = true THEN
                        'NOT NULL'
                    ELSE
                        'NULL'
                    END as column_not_null,
                    a.attnum as attnum,
                    e.max_attnum as max_attnum
                FROM
                    pg_catalog.pg_attribute a
                    INNER JOIN (
                        SELECT c.oid,
                            n.nspname,
                            c.relname
                        FROM pg_catalog.pg_class c
                        LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                        WHERE c.relname ~ ('^('||tablename||')$')
                        AND pg_catalog.pg_table_is_visible(c.oid)
                        ORDER BY 2, 3) b
                    ON a.attrelid = b.oid
                    INNER JOIN (
                        SELECT
                            a.attrelid,
                            max(a.attnum) as max_attnum
                        FROM pg_catalog.pg_attribute a
                        WHERE a.attnum > 0
                        AND NOT a.attisdropped
                        GROUP BY a.attrelid) e
                    ON a.attrelid=e.attrelid
                    WHERE a.attnum > 0
                    AND NOT a.attisdropped
                    ORDER BY a.attnum
            LOOP
                IF column_record.attnum = 1 THEN
                    temp_value:='CREATE TABLE '||column_record.schema_name||'.'||column_record.table_name||' (';
                ELSE
                    temp_value:=temp_value||',';
                END IF;
                IF column_record.attnum <= column_record.max_attnum THEN
                    temp_value:=temp_value||chr(10)||
                         '    '||column_record.column_name||' '||column_record.column_type||' '||column_record.column_default_value||' '||column_record.column_not_null;
                END IF;
            END LOOP;
            -- Record constraints
            FOR const_record IN
                SELECT
                    (','||chr(10)||'    CONSTRAINT '||conname||' '||pg_get_constraintdef(c.oid)||
                    CASE WHEN contype = 'f' THEN
                        ' MATCH SIMPLE '||chr(10)||
                        '        ON UPDATE NO ACTION ON DELETE NO ACTION '
                    ELSE '' END) as text,
                    conindid
                FROM   pg_constraint c
                JOIN   pg_namespace n ON n.oid = c.connamespace
                WHERE  contype IN ('f', 'p ')
                AND    conrelid = regclass(tablename)::oid
                AND    n.nspname = schema_name -- your schema here
                ORDER  BY conrelid::regclass::text, contype DESC
            LOOP
                temp_const := temp_const||const_record.text;
                indexes_not_in := indexes_not_in||const_record.conindid;
            END LOOP;
            FOR index_record IN
                select indexrelid,pg_get_indexdef(indexrelid) as text
                from pg_index
                where indrelid ='user'::regclass
                and NOT(indexrelid = any(indexes_not_in))
            LOOP
                temp_index := temp_index||index_record.text||';'||chr(10);
            END LOOP;
            return_value:=temp_value||temp_const||')';
            select relhasoids into oidcheck
            from pg_class,pg_namespace
            where pg_class.relnamespace=pg_namespace.oid
            and pg_namespace.nspname=schema_name
            and pg_class.relname=tablename
            and pg_class.relkind='r';
            if oidcheck = true then
                return_value:=return_value||' WITH (OIDS=FALSE);';
            else
                return_value:=return_value||' WITHOUT OIDS;';
            end if;
                return_value:=return_value||chr(10)||'ALTER TABLE redacao OWNER TO postgres;'||chr(10);
                return_value:=return_value||temp_index;
        ELSIF type_name = 'sequence' THEN
            /*
             * Generate Sequence Statement
             */
            FOR column_record IN
                SELECT
                    'CREATE SEQUENCE '||a.sequence_name||chr(10)||
                    'INCREMENT '||a.increment||chr(10)||
                    'MINVALUE '||a.minimum_value||chr(10)||
                    'MAXVALUE '||a.maximum_value||chr(10)||
                    'START '||a.start_value||chr(10)||
                    'CACHE 1'||CASE WHEN cycle_option = 'YES' THEN ' CYCLE' ELSE '' END ||';'||chr(10)||
                    'ALTER TABLE '||a.sequence_name||' OWNER TO '||a.sequence_catalog||';' as text
                FROM information_schema.sequences a
                WHERE a.sequence_schema = schema_name
                AND a.sequence_name ~ ('^('||tablename||')$')
            LOOP
                temp_value := column_record.text;
            END LOOP;
            return_value := temp_value;
        ELSIF type_name = 'view' THEN
            /*
             * Generate View Statement
             */
            FOR column_record IN
                select
                    'CREATE OR REPLACE VIEW '||viewname||' AS '||chr(10)||
                    pg_get_viewdef(viewname::regclass, true)||chr(10)||
                    'ALTER TABLE '||viewname||' OWNER TO '||viewowner||';'||chr(10) as view_def
                FROM pg_catalog.pg_views vw
                WHERE vw.schemaname = schema_name
                AND vw.viewname = tablename
            LOOP
                temp_value := temp_value||column_record.view_def;
            END LOOP;
            return_value := temp_value;
        ELSIF type_name = 'trigger' THEN
            /*
             * Generate Trigger Statement
             */
            FOR column_record IN
                SELECT  DISTINCT
                    CASE WHEN pr.prorettype = 'pg_catalog.trigger'::pg_catalog.regtype THEN
                        pg_get_triggerdef(tr.oid)
                    ELSE
                        NULL
                    END as trigger_def
                FROM pg_catalog.pg_class as c
                INNER JOIN pg_catalog.pg_attribute as a ON (a.attrelid = c.oid)
                INNER JOIN pg_catalog.pg_type as t ON (t.oid = a.atttypid)
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN pg_catalog.pg_tablespace ts ON ts.oid = c.reltablespace
                LEFT JOIN pg_trigger tr ON replace(tr.tgrelid::regclass::text, '\"', '') = c.relname
                LEFT JOIN pg_proc pr ON pr.oid = tr.tgfoid
                WHERE a.attnum > 0      -- no system cols
                AND NOT attisdropped    -- no dropped cols
                AND c.relkind = 'r'
                AND tr.tgisinternal is not true
                AND tr.tgname IS NOT NULL
                AND n.nspname = schema_name
                AND tr.tgname = tablename
            LOOP
                temp_value := temp_value||column_record.trigger_def||';';
            END LOOP;
            return_value := temp_value;
        ELSIF type_name = 'function' THEN
            /*
             * Generate Function Statement
             */
            FOR column_record IN
                SELECT *,pg_get_functiondef(f.oid) as text
                FROM pg_catalog.pg_proc f
                INNER JOIN pg_catalog.pg_namespace n ON (f.pronamespace = n.oid)
                WHERE n.nspname = schema_name
            AND f.proname = tablename
            LOOP
                temp_value := temp_value||column_record.text;
            END LOOP;
            return_value := temp_value;
        ELSE
            return_value := '';
        END IF;
        RETURN return_value;
    END;
\$BODY\$
LANGUAGE 'plpgsql' COST 100.0 SECURITY INVOKER;
EOF;
    }

    /**
     * @param $sql
     * @return PDOStatement
     * @throws DBV_Exception
     */
    public function query($sql)
    {
        try {
            return $this->connection->query($sql);
        } catch (PDOException $e) {
            throw new DBV_Exception($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * @return array
     */
    public function getSchema()
    {
        return array_merge(
            $this->getSequences(),
            $this->getTables(),
            $this->getViews(),
            $this->getFunctions(),
            $this->getTriggers()
        );
    }

    /**
     * @param bool|false $prefix
     * @return array
     * @throws DBV_Exception
     */
    public function getSequences($prefix = false)
    {
        $return = array();
        $result = $this->query('SELECT sequence_name FROM information_schema.sequences;');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }
        return $return;
    }

    /**
     * @param bool|false $prefix
     * @return array
     * @throws DBV_Exception
     */
    public function getTables($prefix = false)
    {
        $return = array();

        $result = $this->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = '" . $this->schema . "';");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }

        return $return;
    }

    /**
     * @param bool|false $prefix
     * @return array
     * @throws DBV_Exception
     */
    public function getViews($prefix = false)
    {
        $return = array();
        $result = $this->query("SELECT viewname FROM pg_catalog.pg_views WHERE schemaname = '" . $this->schema . "';");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }
        return $return;
    }

    /**
     * @param bool|false $prefix
     * @return array
     * @throws DBV_Exception
     */
    public function getTriggers($prefix = false)
    {
        $return = array();
        $result = $this->query('SELECT DISTINCT trigger_name from information_schema.triggers;');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }
        return $return;
    }

    /**
     * @param bool|false $prefix
     * @return array
     * @throws DBV_Exception
     */
    public function getFunctions($prefix = false)
    {
        $return = array();
        $result = $this->query("SELECT routine_name FROM information_schema.routines WHERE routine_schema = '" . $this->schema . "' and routine_name <> 'generate_create_statement';");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }

        return $return;
    }

    /**
     * @param $name
     * @return mixed
     * @throws DBV_Exception
     */
    public function getSchemaObject($name)
    {
        switch ($name) {
            case in_array($name, $this->getSequences()):
                $type = 'sequence';
                break;
            case in_array($name, $this->getTables()):
                $type = 'table';
                break;
            case in_array($name, $this->getViews()):
                $type = 'view';
                break;
            case in_array($name, $this->getFunctions()):
                $type = 'function';
                break;
            case in_array($name, $this->getTriggers()):
                $type = 'trigger';
                break;
            default:
                throw new DBV_Exception("<strong>$name</strong> not found in the database");
        }
        $query = "SELECT generate_create_statement('$type', '$name', '" . $this->schema . "');";
        $result = $this->query($query);
        $row = $result->fetch(PDO::FETCH_NUM);
        return $row[0];
    }
}
