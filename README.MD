SPF, DKIM, DMARC, RDNS(PTR)

"¡Hola a todos! En este video, les guiaré paso a paso en la instalación,
 configuración y prueba de envíos de correos con Postfix en un servidor Debian. 
 Aprenderemos cómo preparar el servidor, configurar los archivos clave y realizar pruebas para asegurarnos de que todo funciona correctamente.
 Si estás buscando configurar tu propio servidor de envio correo de manera eficiente, ¡este tutorial es para ti! ¡Comencemos!"


--Instalar aplicaciones necesarias
apt install postfix mailutils php curl net-tools opendkim opendkim-tools
		postconf mail_version
		php -v
		curl -V
		
--Setear nombre de host:
	hostnamectl status
	 hostnamectl set-hostname mail.segurihack.lat
		nano /etc/hosts
			mail.segurihack.lat segurihack
	hostnamectl status

--Crear llave dkim para el sitio:
	mkdir -p /etc/opendkim/keys/segurihack.lat
	opendkim-genkey -s segurihack -d segurihack.lat -D /etc/opendkim/keys/segurihack.lat
	ls /etc/opendkim/keys/segurihack.lat
	chown -R opendkim:opendkim /etc/opendkim
	chmod -R go-rw /etc/opendkim/keys
	
	opendkim:
	nano /etc/opendkim.conf
		Comentar y habilitar para que atravez del milter protocol encuentre el servicio:
			#ket                 local:/run/opendkim/opendkim.sock
			Socket                  inet:8891@localhost
		Añadir:
			KeyTable        /etc/opendkim/KeyTable
			SigningTable    refile:/etc/opendkim/SigningTable

	nano /etc/opendkim/KeyTable
			segurihack.lat_key   segurihack.lat:segurihack:/etc/opendkim/keys/segurihack.lat/segurihack.private
		ls /etc/opendkim/keys/segurihack.lat/segurihack.private
		
	nano /etc/opendkim/SigningTable
		*@segurihack.lat  segurihack.lat_key
	
		systemctl restart opendkim
		systemctl status opendkim
		
		netstat -putona

--Configurar Postfix
	postconf mail_version
	habilitar el log
		postconf maillog_file=/var/log/postfix.log
	
	nano /etc/postfix/main.cf
		myorigin = segurihack.lat
	habilitar dkim protocolo milter:
		milter_default_action = accept
		milter_protocol = 6
		smtpd_milters = inet:localhost:8891
		non_smtpd_milters = inet:localhost:8891
		
	systemctl reload postfix
	systemctl status postfix
	netstat -putona
	
---configurar DNS

	https://dash.cloudflare.com/a1562d8450301172f138b07f87cfd378/segurihack.lat/dns/records
		
		-DMARC
			Le decimos a los servidores de correo como deben comportarse frente a los correos que se considerados spam y que provienen de nuestro dominio.
				https://mxtoolbox.com/DMARCRecordGenerator.aspx?domain=segurihack.lat
					TXT _dmarc v=DMARC1; p=quarantine;rua=mailto:webmaster@segurihack.lat;adkim=r;aspf=r	
						https://mxtoolbox.com/SuperTool.aspx?action=dmarc%3asegurihack.lat&run=toolpage#
			
		-DKIM
			cat /etc/opendkim/keys/segurihack.lat/segurihack.txt
				opendkim-testkey -d segurihack.lat -s segurihack -vvv
				https://mxtoolbox.com/SuperTool.aspx?action=dkim%3asegurihack.lat%3asegurihack&run=toolpage
	
		-SPF
			Archivo donde se autoriza dominios, rangos de direcciones ips o otros spf
			https://www.ipchicken.com/
				TXT @ v=spf1 ip4:186.144.234.61 ~all
						~all sugiere desautorización a las máquinas que no encajen en lo autorizado explícitamente
						-all: Es una política de "hard fail". Los correos que no pasen la validación SPF deben ser rechazados.
						+all: Permite que cualquier IP envíe correos en nombre del dominio. Rara vez se usa, ya que básicamente desactiva el propósito de SPF.
						?all: Es una política neutral. No indica específicamente si el correo debe ser aceptado o rechazado, solo permite que el servidor receptor tome su propia decisión.
				dig txt segurihack.lat
				https://mxtoolbox.com/SuperTool.aspx?action=spf%3asegurihack.lat&run=toolpage
					
		
--- Pruebas:

		https://www.mail-tester.com/ : test-hqoe384y1@srv1.mail-tester.com
		https://smtpserver.com/mail-tester/ : smtp+u67kkfgdbyheph8x@mailtester.smtpserver.com
		
		git clone https://github.com/karlitoxz/SetupPostfix.git
		
			tail -n 30 /var/log/postfix.log
				cat /var/log/postfix.log | grep D259CC03F4
				

	
