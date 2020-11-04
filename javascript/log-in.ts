import { Component, OnInit, ViewChild } from "@angular/core";

import { TabsPage } from '../tabs/tabs';
import { SignUpPage } from '../sign-up/sign-up';

import { BackEndService } from '../../services/back-end-service';
import { ItemService } from '../../services/item-service';
import { MessagingService } from '../../services/messaging-service';
import { MatchingService } from '../../services/matching-service';
import { MojcErrorHandler } from '../../services/mojc-error-handler';
import { MojcStorage } from '../../services/mojc-storage';
import { MojcLocation } from '../../services/mojc-location';
import { MojcFacebook } from '../../services/mojc-facebook';
import { MojcNotification } from '../../services/mojc-notification';
import { MojcConfiguration } from '../../services/mojc-configuration';
import { MojcLanguage } from '../../services/mojc-language';
import { PromptHandler } from '../../services/prompt-handler';

import { MojcError } from '../../services/mojc-error';

import { 
  ENGLISH, 
  ENGLISH_TEXT, 
  FILIPINO, 
  FILIPINO_TEXT, 
  SET_LANGUAGE
} from '../../constants';

import { NavController, AlertController, Platform } from 'ionic-angular';


@Component({
  templateUrl: 'log-in.html',
  selector: 'page-log-in'
})
export class LogInPage implements OnInit {

  loginUser: any;
  signUpUser: any;
  loading: boolean = false;
  showSpinner: boolean = false;
  
  constructor(private backEndService: BackEndService,
              private itemService: ItemService,
              private messagingService: MessagingService,
              private matchingService: MatchingService,
              private mojcErrorHandler: MojcErrorHandler,
              private storage: MojcStorage,
              private mojcLocation: MojcLocation,
              private mojcFacebook: MojcFacebook,
              private mojcConfig: MojcConfiguration,
              private mojcNotif: MojcNotification,
              private mojcLang: MojcLanguage,
              private promptHandler: PromptHandler,
              private navCtrl: NavController,
              public alertCtrl: AlertController,
              private platform: Platform) {

    this.loginUser = {
      name: '',
      password: '',
      deviceToken: ''
    };

    this.signUpUser = {
      username: ''
    };

    this.mojcNotif.getDeviceToken()
    .then(token => {  
        this.loginUser.deviceToken = token;    
    })
    .catch(error => {
        this.mojcErrorHandler.handle(error);
    });
  }
  
  loginTheUser(): Promise<any> {
    //observables
    this.mojcConfig.runChecksOnInterval();
    this.messagingService.getMessagesOnInterval();
    this.mojcNotif.initializeFCMObserver();
   
    //chain calls / promises
    let fcmNotification = null;
    return this.mojcNotif.checkNotificationFromTray()
        .then(res => {
          if(res != false) {
            fcmNotification = res;
          }
          return this.storage.getSavedUsername()
        })
        .then(res => {  
            if(res.length > 0) {
              this.navCtrl.setRoot(TabsPage, {notification: fcmNotification});
            }
            else {
              this.navCtrl.setRoot(SignUpPage);
            }         
          return Promise.resolve({});       
        })  
        .then(res => {
            return this.mojcNotif.getAllNotifications();
        })
        .then(res => {
            return this.messagingService.getChatboxes(false)
        })
        .then(res => {
            return this.matchingService.getUsersForMatching(false)
        })
        .then(res => {
            return this.itemService.getItemsWithoutLocation();
        })
        .catch(error => {
            this.mojcErrorHandler.handle(error);
        });
  }

  loginWithFacebook() {   
    this.showSpinner = true;
    this.mojcFacebook.loginWithFacebook()
        .then(res => { 
          if(res.status != 'connected') {
            Promise.reject('Facebook not connected on login.');
          } 
          this.loginUser.facebook = res.authResponse.userID;    
          this.loginUser.access   = res.authResponse.accessToken;      
          return this.backEndService.loginWithFacebook(this.loginUser);
        })          
        .then(res => {  
          this.showSpinner = false;
          this.loginTheUser();           
        })
        .catch(error => {
          this.showSpinner = false;
          if(error.status != undefined && error.status == 422) {
            var contents = error.json();
            if(contents.message != undefined && contents.message == 'user unavailable') {
              error = new MojcError(error, 'Log in failed.', true);
            }
          }
          this.mojcErrorHandler.handle(error);
        });
  }

  showSetLanguagePrompt() {
    return !this.promptHandler.isDoneFor(SET_LANGUAGE);
  }

  showLanguageAlert() {
      let alert = this.alertCtrl.create();
      alert.setTitle('Choose language');

      alert.addInput({
        type: 'radio',
        label: ENGLISH_TEXT,
        value: ENGLISH,
        checked: true
      });

      alert.addInput({
        type: 'radio',
        label: FILIPINO_TEXT,
        value: FILIPINO,
        checked: false
      });

      alert.addButton('Cancel');
      alert.addButton({
        text: 'OK',
        handler: data => {
          this.mojcLang.saveLanguage(data)
          .then(res => {  
            return this.promptHandler.save(SET_LANGUAGE);
          })
        }
      });
      alert.present();
  }
  
  ngOnInit() {
    this.loading = true;
    this.showSpinner = true;

    this.promptHandler.getCompleted()
        .then(res => {  
            return this.backEndService.getSavedJwt();
        }) 
        .then(res => {     
            if(this.storage.isLoggedIn()) {
              return this.loginTheUser();
            } 
            return Promise.resolve();
        })
        
        .then(res => {  
            this.loading = false;
            this.showSpinner = false;
            if(this.showSetLanguagePrompt()) {
              this.mojcLang.saveLanguage(ENGLISH);
              //this.showLanguageAlert();
            }
            this.mojcLang.loadSavedLanguage();
        })
        .catch(error => {
            this.showSpinner = false;
            this.mojcErrorHandler.handle(error);
        });
    
    //Check geolocation requirements
    this.mojcLocation.checkGeo()
    .then(res => {
         return this.mojcLocation.checkGeoPermission();
     })          
    .catch(error => {
        this.mojcLocation.showLocationAlert();
    })
    .then(res => {
      if (!res && this.platform.version().num > 5) {
        let alert = this.alertCtrl.create({
           title: 'Please turn on location!',
           subTitle: 'So we can find relevant content for you. Thanks!',
           buttons: [{
                      text: 'Ok',
                      handler: () => {
                        this.mojcLocation.requestLocationAuthorization();
                      }
                    }]
         });
         alert.present();
        }
    })          
  }
  
}
