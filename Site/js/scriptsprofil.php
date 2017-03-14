<script>
    toDate = function(dateMod) {
        return new Date(dateMod);
    }
    var app = angular.module("app_profil", []);
    app.controller("ctrl", function($scope) {

        $scope.eleves = <?php echo phpSelectQuery('select id_utilisateur, nom, prenom, id_groupe,code_acces, actif, courriel, telephone, sexe, username, password, administrateur from utilisateurs order by nom ASC')?>;

        $scope.liste_activites = <?php echo phpSelectQuery('select * from activites where hidden=false or hidden is null')?>;
        $scope.activites = <?php 
                            echo phpSelectQuery("select a.id_activite, a.nom_activite, ap.date_activite, ap.heure_debut, a.duree, a.ponderation, ap.frais, ap.endroit, a.commentaire,                           ua.id_eleve_activite, ap.id_activite_prevue
                                                from utilisateur_activites ua, activites_prevues ap, activites a 
                                                where ua.id_utilisateur = {$_SESSION['uid']} and 
                            (ap.hidden = 0 or ap.hidden is null) and
                                                ua.id_activite_prevue = ap.id_activite_prevue and
                                                ap.id_activite = a.id_activite
                            order by ap.presences_prises
                            ")
                            ?>;

        $scope.activites_responsable = <?php 
                  echo phpSelectQuery("select * from activites, activites_prevues where (activites_prevues.hidden=0 or activites_prevues.hidden is null) and activites_prevues.id_activite = activites.id_activite and activites.id_activite in(select id_activite from activites_prevues where responsable = ".$_SESSION['uid']." order by activites_prevues.presences_prises asc )")
                  ?>;



        $scope.points_debut = <?php echo phpSelectQuery('select sum(ponderation) as points_debut, utilisateurs.id_utilisateur
            from utilisateurs, activites, activites_prevues, utilisateur_activites, sessions, groupes 
            where activites_prevues.id_activite = activites.id_activite 
            and utilisateur_activites.id_activite_prevue = activites_prevues.id_activite_prevue 
            and utilisateur_activites.id_utilisateur = utilisateurs.id_utilisateur
            and utilisateurs.id_groupe = groupes.id_groupe
            and groupes.id_session = sessions.id_session
            and activites_prevues.date_activite > sessions.debut_session
            and activites_prevues.date_activite < sessions.mi_session
            and utilisateur_activites.present = 1
            and utilisateur_activites.id_utilisateur = '.$_SESSION['uid'].'
            group by utilisateurs.id_utilisateur')?>;

        $scope.points_fin = <?php echo phpSelectQuery('select sum(ponderation) as points_fin, utilisateurs.id_utilisateur
            from utilisateurs, activites, activites_prevues, utilisateur_activites, sessions, groupes 
            where activites_prevues.id_activite = activites.id_activite 
            and utilisateur_activites.id_activite_prevue = activites_prevues.id_activite_prevue 
            and utilisateur_activites.id_utilisateur = utilisateurs.id_utilisateur
            and utilisateurs.id_groupe = groupes.id_groupe
            and groupes.id_session = sessions.id_session
            and activites_prevues.date_activite > sessions.mi_session
            and activites_prevues.date_activite < sessions.fin_session
            and utilisateur_activites.present = 1
            and utilisateur_activites.id_utilisateur = '.$_SESSION['uid'].'
            group by utilisateurs.id_utilisateur')?>;

        $scope.masquerPresence = true;
        
        $scope.SESSION = 1;




      $scope.pts3 = function(id){
            let x = $scope.pointsFinForEleve(id)+ $scope.pointsBonusForEleve(id)+ parseInt($scope.pointsDebutForEleve(id));

            return x;
        }

        $scope.now = new Date();

        $scope.activites_prevues = <?php echo phpSelectQuery('select * from activites_prevues where responsable = '.$_SESSION['uid'].' and hidden=false or hidden is null order by presences_prises, date_activite ')?>;

        
        try {
            $scope.ensemble = <?php echo phpSelectQuery('select id_groupe, nom_groupe, id_prof, ensemble, nom_session, groupes.id_session from groupes, sessions where groupes.id_session = sessions.id_session and groupes.id_groupe in (select id_groupe from utilisateurs where id_utilisateur = '.$_SESSION['uid'].') order by nom_groupe ASC')?> [0].ensemble;
        } catch (err) {
            $scope.ensemble = 0;
        }
        $scope.eleves_activites = <?php echo phpSelectQuery('select * from utilisateur_activites')?>;


        $scope.comptesAdministrateur = <?php echo phpSelectQuery('select * from utilisateurs where administrateur >= 1 and code_acces="" order by nom ASC')?>;
        $scope.activiteFromId = function(id) {

            let act = $scope.activites.filter(function(ac) {
                return ac.ID_Activite == id;
            })[0];

            return act;

        }


        

        $scope.dateToString = function(_date) {

            return (_date.toString());

        }

        $scope.eleveFromId = function(id) {
            return $scope.eleves.filter(function(el) {

                return el.id_utilisateur == id;
            })[0];
        }

        $scope.annuler_participation = function(activite) {


            if (confirm("Voulez-vous réellement annuler votre participation à l'activité: ".concat(activite.nom_activite).concat(" le ").concat(activite.date_activite))) {

                $.ajax({
                    type: "POST",
                    url: "php_scripts/annuler_participation.php",
                    data: {
                        'id_act_utilisateur': activite.id_eleve_activite,
                        'date_activite': $('#date_act').val(),
                        'heure': $('#heure_deb').val()
                    },
                    success: function(data) {
                        
                        if (data == "Vous avez quitté l'activité avec succès") {
                            location.reload();
                        }
                    },
                    error: function(req) {
                        alert("erreur");
                    }
                });

            } else {
                
            }
        }


        $scope.show_params = function(activite) {
            
            $('#modal_mod_planif').modal('open');
            $('#ID_ACT_PLAN').val(activite.id_activite_prevue);
            $('#mod_nom_act').val(activite.id_Activite);
            $('#mod_nom_act').material_select();
            $('#mod_date_act').val(activite.date_activite);
            $('#mod_heure_deb').val(activite.heure_debut);
            $('#mod_participants_max').val(activite.participants_max);
            $('#mod_frais').val(activite.frais);
            $('#mod_endroit').val(activite.endroit);
            $('#mod_responsable').val(activite.responsable);
            $('#mod_responsable').material_select();
            $('.ACTIVER').addClass("active");


        }




        $scope.enregistrerPresence = function(activite_prevue) {
            var values = new Array();
            $.each($("input[name='presenceActivite" + activite_prevue + "']:checked"), function() {
                values.push($(this).val());
            });


            $.ajax({
                type: "POST",
                url: "php_scripts/prendrePresence.php",
                data: {
                    'PRESENTS': values,
                    'ACTIVITE': activite_prevue
                }, //TODO: CHANGE PROF ID
                success: function(data) {
                    location.reload();
                },
                error: function(req) {
                    alert("erreur");
                }
            });



        }


        $scope.getElevesForActivitePrevue = function(activite) {

            let liste_el_ac = ($scope.eleves_activites.filter(function(ac) {
                return ac.id_activite_prevue == activite;
            }));

            
            var listeId = liste_el_ac.map(function(a) {
                return a.id_utilisateur;
            });

            let arr = [];

            for (var i = 0; i < listeId.length; i++) {
                arr.push($scope.eleveFromId(listeId[i]));
            }
            return arr;

        }

        $scope.getPresenceForEleve = function(activite_prevue, eleve) {
            try {
                let present = ($scope.eleves_activites.filter(function(ac) {
                    return ac.ID_Activite_Prevue == activite_prevue && ac.ID_Utilisateur == eleve;
                }))[0].Present;




                if (present == 1) {
                    return true;
                } else return false;
            } catch (err) {
                return false
            }


        }

        $scope.annulerActivite = function(activite) {

            if (confirm("Vous êtes sur le point de supprimer cette activité, êtes vous sûr?") == true) {
                $.ajax({
                    type: "POST",
                    url: "php_scripts/annulerActivite.php",
                    data: {
                        'ID_ACTIVITE': activite,
                    }, //TODO: CHANGE PROF ID
                    success: function(data) {

                        location.reload();

                    },
                    error: function(req) {
                        alert("erreur");
                    }
                });

            }




        }




        $scope.modifierActivitePrevue = function() {

            $.ajax({
                type: "POST",
                url: "php_scripts/modifierActivitePrevue.php",
                data: {
                    'ID_ACTIVITE_PREVUE': $('#ID_ACT_PLAN').val(),
                    'ID_ACTIVITE': $('#mod_nom_act').val(),
                    'DATE_ACT': $('#mod_date_act').val(),
                    'HEURE_ACT': $('#mod_heure_deb').val(),
                    'PARTICIPANTS_MAX': $('#mod_participants_max').val(),
                    'FRAIS': $('#mod_frais').val(),
                    'ENDROIT': $('#mod_endroit').val(),
                    'RESPONSABLE': $('#mod_responsable').val()
                }, //TODO: CHANGE PROF ID
                success: function(data) {


                    if (data.trim() == "L'activité a été modifiée avec succès!") {
                        location.reload();
                    }



                },
                error: function(req) {
                    alert("erreur");
                }
            });


        }



        $scope.pointsDebutForEleve = function(id) {

            let pts = 0;
            try {
                pts = $scope.points_debut.filter(function(el) {

                    return el.id_utilisateur == id;
                })[0].points_debut;

                if (pts > 5) {
                    return 5;
                }
            } catch (err) {}
            return parseInt(pts);



        }

        $scope.penaliteForEleve = function(id){
            try{

            let pts = parseInt($scope.penalites.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].penalite);
            return pts;
        }
            catch(err){
        
                return 0;
            }

        }


        $scope.pointsFinForEleve = function(id) {

            let pts = 0;
            try {
                pts = $scope.points_fin.filter(function(el) {

                    return el.id_utilisateur == id;
                })[0].points_fin;

                if (pts > 5) {
                    return 5;
                }
            } catch (err) {}
            return parseInt(pts);
        }



        $scope.pointsBonusForEleve = function(id) {
            let pts_fin = 0;
            let pts_debut = 0;

            try {
                pts_fin = $scope.points_fin.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].points_fin;
            } catch (err) {}


            try {
                pts_debut = $scope.points_debut.filter(function(el) {

                    return el.id_utilisateur == id;
                })[0].points_debut;
            } catch (err) {}

            let pts_bonus = 0;

            if (pts_fin > 5) {
                pts_bonus += pts_fin - 5;
            }

            if (pts_debut > 5) {
                pts_bonus += pts_debut - 5;
            }

            if(pts_bonus > 5){
                return parseInt(5);
            }else return parseInt(pts_bonus);
        }

        $scope.pointsReguliersForEleve = function(id) {
            let pts_fin = 0;
            let pts_debut = 0;
            try {
                pts_fin = $scope.points_fin.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].points_fin;
            } catch (err) {}


            try {
                pts_debut = $scope.points_debut.filter(function(el) {

                    return el.id_utilisateur == id;
                })[0].points_debut;
            } catch (err) {}


            let pts_reg = 0;

            if (parseInt(pts_debut) + parseInt(pts_fin)  > 5) {
                pts_reg = 5;
            } else pts_reg = parseInt(pts_debut) + parseInt(pts_fin);
            
            return parseInt(pts_reg);
        }




        $scope.pointsBonusEnsemble1ForEleve = function(id) {

            let pts_fin = 0;
            let pts_debut = 0;
            try {
                pts_fin = $scope.points_fin.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].points_fin;
            } catch (err) {}


            try {
                pts_debut = $scope.points_debut.filter(function(el) {

                    return el.id_utilisateur == id;
                })[0].points_debut;
            } catch (err) {}


            let pts_reg = (parseInt((parseInt(pts_debut) + parseInt(pts_fin))));



            if (pts_reg > 5) {

                    return parseInt(pts_reg - 5);
             
            }
            else return 0;



        }


        $scope.pointsEnsemble2 = function(id) {

            let pts_fin = 0;
            let pts_debut = 0;

            try {
                pts_fin = $scope.points_fin.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].points_fin;
                pts_fin = parseInt(pts_fin);
            } catch (err) {}
            pts_fin = parseInt(pts_fin);

            try {
                pts_debut = $scope.points_debut.filter(function(el) {
                    return el.id_utilisateur == id;
                })[0].points_debut;
                pts_debut = parseInt(pts_debut);
            } catch (err) {}
            pts_debut= parseInt(pts_debut);
            let pts_totaux = parseInt(pts_fin) + parseInt(pts_debut);

            if (pts_totaux > 5) {
                pts_totaux = 5;
            }


            return pts_totaux;


        }




    });
</script>