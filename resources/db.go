package resources

import (
	"fmt"
	"io/ioutil"
	"os"

	"github.com/jmoiron/sqlx"
	"gopkg.in/yaml.v3"
)

var DB *sqlx.DB

func GetDB() (*sqlx.DB, error) {

	if DB != nil {
		return DB, nil
	}

	var err error

	// file get content auth-mysql.yml
	content := file_get_contents("auth-mysql.yml")

	if content == "" {
		fmt.Println("get content failed")
		return nil, err
	}

	// decode yml to get dsn
	/*
	   dsn:
	     host: 1.2.33.44
	     port: 3306
	     schema: mqtt-auth
	     charset: utf8mb4
	     login-name: dev_user
	     login-password: aassddff
	     max-open-conns: 10
	     max-idle-conns: 5
	*/
	//dsn struct
	type Dsn struct {
		Host          string `yaml:"host"`
		Port          int    `yaml:"port"`
		Schema        string `yaml:"schema"`
		Charset       string `yaml:"charset"`
		LoginName     string `yaml:"login-name"`
		LoginPassword string `yaml:"login-password"`
		MaxOpenConns  int    `yaml:"max-open-conns"`
		MaxIdleConns  int    `yaml:"max-idle-conns"`
	}

	// auth-mysql.yml struct
	type AuthMysql struct {
		Dsn Dsn `yaml:"dsn"`
	}

	// auth-mysql.yml decode
	var authMysql AuthMysql
	err = yaml.Unmarshal([]byte(content), &authMysql)
	if err != nil {
		fmt.Println("unmarshal failed,", err)
		return nil, err
	}

	DB, err = sqlx.Connect("mysql", fmt.Sprintf("%s:%s@tcp(%s:%d)/%s?charset=%s",
		authMysql.Dsn.LoginName,
		authMysql.Dsn.LoginPassword,
		authMysql.Dsn.Host,
		authMysql.Dsn.Port,
		authMysql.Dsn.Schema,
		authMysql.Dsn.Charset,
	))

	if err != nil {
		fmt.Println("open mysql failed,", err)
		return nil, err
	}

	DB.SetMaxOpenConns(1000) // The default is 0 (unlimited)
	DB.SetMaxIdleConns(10)   // defaultMaxIdleConns = 2
	DB.SetConnMaxLifetime(0) // 0, connections are reused forever.
	return DB, nil
}

func file_get_contents(filename string) string {
	fh, err := os.Open(filename)
	if err != nil {
		fmt.Println("open file failed,", err)
		return ""
	}

	defer fh.Close()

	content, err := ioutil.ReadAll(fh)
	if err != nil {
		fmt.Println("read file failed,", err)
		return ""
	}

	return string(content)
}
