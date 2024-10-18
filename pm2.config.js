module.exports = {
    apps : [{
      name   : "go-mqtt",
      script : "./queuemounts",
      args : ["--conf=auth-mysql.yml"],
      watch: false
    },
    {
      name :"php-admin",
      script : "php",
      env:{
        "PHP_CLI_SERVER_WORKERS" : 8
      },
      args : ["-S","0.0.0.0:10000","-t","./control-panel"],
      watch: false
    }
  ]
}