package org.novosga.painel.client;

import org.novosga.painel.client.config.PainelConfig;
import org.novosga.painel.model.Senha;
import org.novosga.painel.client.ui.SysTray;
import java.io.File;
import java.io.FileNotFoundException;
import java.util.logging.Level;
import java.util.logging.LogManager;
import java.util.logging.Logger;
import javafx.application.Application;
import javafx.application.Platform;
import javafx.stage.Stage;
import org.novosga.painel.client.fonts.FontLoader;
import org.novosga.painel.client.ui.Controller;
import org.novosga.painel.client.network.PacketListener;
import org.novosga.painel.client.network.PacketListenerFactory;
import org.novosga.painel.event.SenhaEvent;

/**
 * Novo SGA Painel Main class
 * @author rogeriolino
 */
public class Main extends Application {
    
    private static final Logger LOG = Logger.getLogger(Main.class.getName());
    public static final String DEFAULT_PROTOCOL = "UDP";
    public static final int DEFAULT_RECEIVE_PORT = 8888;
    public static final int DEFAULT_SEND_PORT = 9999;
    
    private PacketListener listener;
    private PainelService service;
    private PainelFx painel;
    private Controller controller;
    private PainelConfig config;

    @Override
    public void start(Stage stage) throws Exception {
        Platform.setImplicitExit(false);
        config = new PainelConfig();
        
        // Carregar configurações do Logger
        try {
            LogManager.getLogManager().readConfiguration(Main.class.getResourceAsStream("logger.properties"));
        } catch (Exception e) {
            // Nunca deve acontecer, ja que o arquivo logger.properties deve estar dentro do proprio .jar
            LOG.log(Level.SEVERE, "Falha carregando configurações do logger, utilizando configurações default.", e);
        }
        
        // Carrega a configuracao do painel
        boolean configOk = false;
        try {
            config.load();
            configOk = true;
        } catch (FileNotFoundException e) {
            final String message = "Configuração do painel não encontrada.\nÉ necessário efetuar a configuração do Painel antes de iniciar seu uso.";
            LOG.log(Level.SEVERE, message, e);
        } catch (Exception e) {
            final String message = "Ocorreu um erro carregando as configurações do Painel, verifique se o arquivo de configuração não está em uso e suas permissões.";
            LOG.log(Level.SEVERE, message, e);
        }
        
        FontLoader.registerAll();
        
        // Inicia o servidor para receber mensagens
        String protocol = config.get(PainelConfig.KEY_PROTOCOL).getValue();
        try {
            painel = new PainelFx(this);
            Stage painelStage = new Stage();
            painelStage.initOwner(stage);
            painel.start(painelStage);
            
            listener = PacketListenerFactory.create(
                    protocol, 
                    config.get(PainelConfig.KEY_PORT_RECEIVE, Integer.class).getValue(),
                    config.get(PainelConfig.KEY_PORT_SEND, Integer.class).getValue(),
                    config.get(PainelConfig.KEY_SERVER).getValue()
            );
            listener.setOnNovaSenhaEvent(new SenhaEvent() {
                @Override
                public void handle(Senha senha) {
                    painel.chamaSenha(senha);
                }
            });
            
            listener.inicia();
            
            service = new PainelService(this);
            controller = new Controller(this);
            controller.getStage().initOwner(stage);
            try {
                service.registerAndLoad(new Runnable() {
                    @Override
                    public void run() {
                        controller.update();
                    }
                });
            } catch (Exception e) {
            } finally {
                controller.update();
            }
            
            
            if (configOk) {
                painel.show();
            } else {
                controller.show();
            }
            try {
                // Adiciona o painel na banjeida do sistema
                new SysTray(this);
            } catch (final Exception e) {
                LOG.log(Level.SEVERE, e.getMessage(), e);
            }
        } catch (final Exception e) {
            LOG.log(Level.SEVERE, e.getMessage(), e);
            System.exit(1);
        }
    }

    public PainelFx getPainel() {
        return painel;
    }

    public Controller getController() {
        return controller;
    }

    public PainelConfig getConfig() {
        return config;
    }

    public PacketListener getListener() {
        return listener;
    }

    public PainelService getService() {
        return service;
    }

    /**
     * The main() method is ignored in correctly deployed JavaFX application.
     * main() serves only as fallback in case the application can not be
     * launched through deployment artifacts, e.g., in IDEs with limited FX
     * support. NetBeans ignores main().
     *
     * @param args the command line arguments
     */
    public static void main(String[] args) {
        launch(args);
    }
    
    public static File getWorkingDirectory() {
        final String applicationName = "PainelSGA";
        final String userHome = System.getProperty("user.home", ".");
        final File workingDirectory;
        final String sysName = System.getProperty("os.name").toLowerCase();

        if (sysName.contains("linux") || sysName.contains("solaris")) {
            workingDirectory = new File(userHome, '.' + applicationName + '/');
        } else if (sysName.contains("windows")) {
            final String applicationData = System.getenv("APPDATA");
            if (applicationData != null) {
                workingDirectory = new File(applicationData, "." + applicationName + '/');
            } else {
                workingDirectory = new File(userHome, '.' + applicationName + '/');
            }
        } else if (sysName.contains("mac")) {
            workingDirectory = new File(userHome, "Library/Application Support/" + applicationName);
        } else {
            workingDirectory = new File(".");
        }
        if (!workingDirectory.exists() && !workingDirectory.mkdirs()) {
            throw new RuntimeException("The working directory could not be created: " + workingDirectory);
        }
        return workingDirectory;
    }
    
}