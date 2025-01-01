SPF, DKIM, DMARC, RDNS(PTR)

"¡Hola a todos! En este video, les guiaré paso a paso en la instalación,
 configuración y prueba de envíos de correos con la aplicacion Postfix en un servidor Debian. 
 Aprenderemos cómo preparar el servidor, configurar los archivos clave y realizar pruebas para asegurarnos de que todo funciona correctamente.
 Si estás buscando configurar tu propio servidor de envio masivo de  correo de manera eficiente, ¡este tutorial es para ti! ¡Comencemos!"


-- 1. Instalar aplicaciones necesarias
	Bien, como primer paso vamos a instalar las aplicaciones basicas necesarias a nuestro servidor debian en su version 12.
  corriendo como maquina en VMware Workstation 17 -> recuerden que VMware en su version pro es gratuita para entornos personales.

  
apt install postfix mailutils php curl net-tools opendkim opendkim-tools
		postconf mail_version
		php -v
		curl -V
		
-- 2. Setear nombre de host:
		 Este es necesario ya que postfix enviar nuestro nombre de host en el correo saliente, y los servidores de correos entrantes
		 buscaran si ese nombre de servidor de correo existe en nuestros DNS en un registro de tipo A, esta configuración aumentara
		 nuestra calificacion del correo enviado.
	hostnamectl status
	 hostnamectl set-hostname mail.segurihack.lat
		nano /etc/hosts
			mail.segurihack.lat mail
			reboot
	hostnamectl status

-- 3. Crear llave dkim para el sitio:

DKIM (DomainKeys Identified Mail) es un mecanismo de autenticación de correo electrónico que utiliza una firma digital
 para verificar que un mensaje fue enviado y autorizado por el dominio del remitente. Esto se logra añadiendo una firma
 en los encabezados del correo que el servidor receptor puede validar usando una clave pública publicada en el DNS del dominio.
 Ayuda a prevenir la suplantación de identidad y garantiza la integridad del mensaje.
 
 
	mkdir -p /etc/opendkim/keys/segurihack.lat
	opendkim-genkey -s segurihack -d segurihack.lat -D /etc/opendkim/keys/segurihack.lat
	cd  /etc/opendkim/keys/segurihack.lat
	ls
	cat segurihack.private
	cat segurihack.txt
	chown -R opendkim:opendkim /etc/opendkim
	chmod -R go-rw /etc/opendkim/keys
	
	opendkim:
	nano /etc/opendkim.conf
		Comentar y habilitar para que atravez del milter protocol encuentre el servicio:
			#Socket                 local:/run/opendkim/opendkim.sock
			Socket                  inet:8891@localhost
		Añadir:
			#Archivos de ubicación y utilizacion de firmas
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

-- 4. Configurar Postfix

Postfix es un servidor de correo electrónico (MTA, Mail Transfer Agent) de código abierto que se utiliza para enviar,
 recibir y enrutar correos electrónicos. Es conocido por ser seguro, rápido y fácil de configurar, ideal para servidores
 de correo en sistemas Unix y Linux.
 
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
	
--- 5. configurar DNS

	En nuestro servidor DNS del dominio debemos configurar 4 parametros para el envio de correo,un registor A con la ip y 
	el nombre del host desde donde estan saliendo los correos
	SPF, DKIM y DMARC en el servidor DNS de tu dominio es fundamental para proteger tus correos electrónicos contra suplantación 
	de identidad (phishing) y asegurar su legitimidad. SPF especifica qué servidores pueden enviar correos en nombre de tu dominio. 
	DKIM agrega una firma digital para verificar que los mensajes no fueron alterados. 
	DMARC combina SPF y DKIM para definir políticas y reportes sobre cómo tratar correos no autenticados, 
	mejorando la seguridad y la reputación de tu dominio.
	
	en esta ocacion vamos a utilizar cloud  flare, ya que tiene servicios gratuitos para nuestros dominios, ademas que su propagación 
	DNS es muy rapida.

	https://dash.cloudflare.com/a1562d8450301172f138b07f87cfd378/segurihack.lat/dns/records
	
		-SERVER:
			https://www.ipchicken.com/
		A mail 186.144.234.61
		
		-DMARC
			Le decimos a los servidores de correo como deben comportarse frente a los correos que se considerados spam y que
			provienen de nuestro dominio.
				https://mxtoolbox.com/DMARCRecordGenerator.aspx?domain=segurihack.lat
					TXT _dmarc "v=DMARC1; p=quarantine;rua=mailto:webmaster@segurihack.lat;adkim=r;aspf=r"
					
					v=DMARC1: Especifica que es un registro DMARC.
					p=quarantine: Indica que los correos que no pasen la validación deben marcarse como sospechosos y 
					enviarse a la carpeta de spam del destinatario.
					rua=mailto:webmaster@segurihack.lat: Dirección de correo a la que se enviarán los reportes 
					agregados sobre el cumplimiento de DMARC.
					adkim=r: Establece un alineamiento relajado para DKIM, lo que significa que el subdominio también será válido.
					aspf=r: Establece un alineamiento relajado para SPF, permitiendo que subdominios sean considerados válidos.
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
			
		
--- 6. Pruebas:

		https://www.mail-tester.com/ : test-hqoe384y1@srv1.mail-tester.com --NO
		https://smtpserver.com/mail-tester/ : smtp+u67kkfgdbyheph8x@mailtester.smtpserver.com
		
		git clone https://github.com/karlitoxz/SetupPostfix.git
		
			tail -n 30 /var/log/postfix.log
				cat /var/log/postfix.log | grep D259CC03F4
				
		php sendMail.php
		

---  RDNS mismatched se ajusta RDNS, pero se daña el score por que no existe registro A con la ip de ese servidor.

--- 7. Extra RDNS PTR
https://www.ipchicken.com/
	nano /etc/postfix/main.cf
		myhostname = dynamic-ip-18614423461.cable.net.co
	systemctl restart postfix
	systemctl reload postfix
	
	
		
		
				

	
